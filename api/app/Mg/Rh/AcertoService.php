<?php

namespace Mg\Rh;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Mg\Colaborador\ColaboradorCargo;
use Mg\Titulo\MovimentoTitulo;
use Mg\Titulo\MovimentoTituloService;

/**
 * Acerto (Encontro de Contas) — modelo de EVENTOS.
 *
 * Cada acerto é um registro em tblperiodocolaboradoracerto (data, forma B/D/F,
 * valor). A remuneração variável (benefício) NÃO é mais um título: fica só no
 * PeriodoColaborador->valortotal e é entregue via eventos (Bee/dinheiro) — sem
 * movimento financeiro. Os vales/adiantamentos (títulos reais a débito) são
 * baixados por movimentos (tblmovimentotitulo tipo 601, sem liquidação,
 * amarrados ao evento). A trigger fntblmovimentotituloaiauad baixa o saldo.
 *
 * Nada é excluído: um acerto errado é INATIVADO (estornando seus movimentos) e
 * um novo é criado. Vários eventos por colaborador (parcial: parte folha, parte
 * dinheiro, resto Bee).
 */
class AcertoService
{
    // -------------------------------------------------------------------------
    // Listagem
    // -------------------------------------------------------------------------

    public static function listarAcertos(int $codperiodo, int $dias = 5): Collection
    {
        $pcs = PeriodoColaborador::where('codperiodo', $codperiodo)
            ->with(['Colaborador.Pessoa', 'Setor.UnidadeNegocio'])
            ->get();

        $eventosPorPc = PeriodoColaboradorAcerto::whereIn('codperiodocolaborador', $pcs->pluck('codperiodocolaborador'))
            ->whereNull('inativo')
            ->get()
            ->groupBy('codperiodocolaborador');

        return $pcs->map(function ($pc) use ($eventosPorPc) {
            $codpessoa = $pc->Colaborador->codpessoa ?? null;
            $eventos   = $eventosPorPc->get($pc->codperiodocolaborador, collect());

            $efetivado  = $eventos->isNotEmpty();
            $financeiro = $eventos->whereIn('forma', [PeriodoColaboradorAcerto::FORMA_BEE, PeriodoColaboradorAcerto::FORMA_DINHEIRO])->sum(fn ($a) => abs($a->saldo));
            $folha      = $eventos->where('forma', PeriodoColaboradorAcerto::FORMA_FOLHA)->sum(fn ($a) => abs($a->saldo));

            [$remanescente_valor, $remanescente_qtd] = static::remanescente($codpessoa);

            return (object) [
                'codperiodocolaborador' => $pc->codperiodocolaborador,
                'codcolaborador'        => $pc->codcolaborador,
                'codpessoa'             => $codpessoa,
                'nome'                  => $pc->Colaborador?->Pessoa?->pessoa ?? '—',
                'status_periodo'        => $pc->status,
                'status_acerto'         => $efetivado ? 'efetivado' : 'pendente',
                'creditos'              => round($financeiro, 2),
                'debitos'               => round($folha, 2),
                'financeiro'            => round($financeiro, 2),
                'folha'                 => round($folha, 2),
                'remanescente_valor'    => round($remanescente_valor, 2),
                'remanescente_qtd'      => $remanescente_qtd,
                'codunidadenegocio'     => $pc->Setor?->UnidadeNegocio?->codunidadenegocio ?? null,
                'unidade'               => $pc->Setor?->UnidadeNegocio?->descricao ?? null,
            ];
        });
    }

    protected static function remanescente(?int $codpessoa): array
    {
        if (!$codpessoa) {
            return [0, 0];
        }

        $titulos = DB::select("
            SELECT ABS(saldo) AS valor
            FROM tbltitulo
            WHERE codpessoa = :codpessoa
              AND saldo != 0
        ", ['codpessoa' => $codpessoa]);

        $valor = array_sum(array_column($titulos, 'valor'));
        $qtd   = count($titulos);

        return [$valor, $qtd];
    }

    // -------------------------------------------------------------------------
    // Benefício já entregue (para calcular o que resta do valortotal)
    // -------------------------------------------------------------------------

    /**
     * Quanto do benefício (valortotal) já foi considerado nos acertos ativos
     * = soma da coluna `rubricas` (o quanto do benefício cada acerto pagou).
     */
    protected static function beneficioJaEntregue(Collection $acertos): float
    {
        return (float) $acertos->sum('rubricas');
    }

    // -------------------------------------------------------------------------
    // Títulos do colaborador (modal)
    // -------------------------------------------------------------------------

    public static function buscarTitulos(int $codperiodocolaborador, int $dias = 5): array
    {
        $pc = PeriodoColaborador::with(['Colaborador.Pessoa', 'Periodo'])
            ->findOrFail($codperiodocolaborador);

        $colaborador = $pc->Colaborador;
        $pessoa      = $colaborador->Pessoa;
        $periodo     = $pc->Periodo;

        // Cargo mais recente
        $cargo = ColaboradorCargo::where('codcolaborador', $colaborador->codcolaborador)
            ->with('Cargo')
            ->orderBy('codcolaboradorcargo', 'desc')
            ->first();

        $salario = $cargo ? $cargo->salario : null;

        // Tempo de casa
        $tempoCasa = null;
        if ($colaborador->contratacao) {
            $contratacao = Carbon::parse($colaborador->contratacao);
            $diff        = $contratacao->diff(Carbon::now());
            if ($diff->y > 0) {
                $tempoCasa = $diff->y . ' ano' . ($diff->y > 1 ? 's' : '');
                if ($diff->m > 0) {
                    $tempoCasa .= ' e ' . $diff->m . ' ' . ($diff->m == 1 ? 'mês' : 'meses');
                }
            } elseif ($diff->m > 0) {
                $tempoCasa = $diff->m . ' ' . ($diff->m == 1 ? 'mês' : 'meses');
            } else {
                $tempoCasa = $diff->d . ' dia' . ($diff->d != 1 ? 's' : '');
            }
        }

        // Acertos ativos — base do cálculo do benefício restante (soma de `rubricas`).
        $acertos = PeriodoColaboradorAcerto::where('codperiodocolaborador', $codperiodocolaborador)
            ->whereNull('inativo')
            ->get();

        $beneficioRestante = round(((float) $pc->valortotal) - static::beneficioJaEntregue($acertos), 2);

        // Vales/adiantamentos reais (débitos/créditos com saldo != 0).
        // Exclui o título RH (952) legado — o benefício agora é sintético.
        $titulosReais = DB::select("
            SELECT
                t.codtitulo,
                t.numero,
                t.vencimento,
                t.saldo,
                t.debitosaldo,
                t.creditosaldo,
                tt.tipotitulo,
                t.codtipotitulo,
                CASE
                    WHEN t.saldo > 0 AND t.vencimento <= CURRENT_DATE + CAST(:dias AS integer)
                        THEN t.saldo
                    ELSE 0
                END AS sugestao_descontando,
                CASE
                    WHEN t.saldo < 0 AND t.vencimento <= CURRENT_DATE + CAST(:dias2 AS integer)
                        THEN ABS(t.saldo)
                    ELSE 0
                END AS sugestao_pagando
            FROM tbltitulo t
            JOIN tbltipotitulo tt ON tt.codtipotitulo = t.codtipotitulo
            WHERE t.codpessoa = :codpessoa
              AND t.saldo != 0
              AND t.codtipotitulo <> 952
            ORDER BY t.vencimento, t.saldo, t.codtitulo
        ", [
            'codpessoa' => $colaborador->codpessoa,
            'dias'      => $dias,
            'dias2'     => $dias,
        ]);

        // Linha sintética do benefício (remuneração variável ainda não entregue).
        // saldo < 0 = crédito (a pagar/entregar); > 0 = a descontar.
        $titulos = [];
        if (abs($beneficioRestante) >= 0.01) {
            $titulos[] = (object) [
                'codtitulo'            => null,
                'numero'               => 'Remuneração Variável',
                'vencimento'           => null,
                'saldo'                => -$beneficioRestante,
                'debitosaldo'          => $beneficioRestante < 0 ? abs($beneficioRestante) : 0,
                'creditosaldo'         => $beneficioRestante > 0 ? $beneficioRestante : 0,
                'tipotitulo'           => 'Benefício',
                'codtipotitulo'        => 0,
                'sugestao_descontando' => $beneficioRestante < 0 ? abs($beneficioRestante) : 0,
                'sugestao_pagando'     => $beneficioRestante > 0 ? $beneficioRestante : 0,
            ];
        }
        $titulos = array_merge($titulos, $titulosReais);

        return [
            'colaborador' => [
                'codperiodocolaborador'   => $pc->codperiodocolaborador,
                'codpessoa'               => $colaborador->codpessoa,
                'nome'                    => $pessoa->pessoa ?? '—',
                'cargo'                   => $cargo?->Cargo?->cargo ?? null,
                'tempo_casa'              => $tempoCasa,
                'salario'                 => $salario ? (float) $salario : null,
                'valortotal'              => (float) $pc->valortotal,
                'beneficio_restante'      => $beneficioRestante,
                'percentual_max_desconto' => (float) ($periodo->percentualmaxdesconto ?? 30),
            ],
            'titulos' => $titulos,
        ];
    }

    // -------------------------------------------------------------------------
    // Efetivação (cria UM evento por confirmação; suporta parcial/múltiplo)
    // -------------------------------------------------------------------------

    public static function efetivar(int $codperiodocolaborador, array $titulos, string $forma, ?string $observacao, ?string $data): array
    {
        $pc = PeriodoColaborador::with(['Colaborador', 'Periodo'])
            ->findOrFail($codperiodocolaborador);

        // O acerto é lançado enquanto o colaborador está ABERTO. Encerrar trava tudo.
        if ($pc->status !== PeriodoService::STATUS_COLABORADOR_ABERTO) {
            throw new \Exception('Colaborador encerrado — reabra para lançar acerto.');
        }

        // Percorre as linhas: total pagando/descontando e movimentos de baixa
        // (só para títulos reais; a linha sintética do benefício tem codtitulo null).
        // Decompõe o acerto: rubricas (benefício = linha sintética codtitulo null),
        // creditos (títulos a crédito pagos) e debitos (títulos a débito descontados).
        $rubricas   = 0.0;
        $creditos   = 0.0;
        $debitos    = 0.0;
        $movimentos = []; // [codtitulo, debito, credito]

        foreach ($titulos as $t) {
            $codtitulo   = $t['codtitulo'] ?? null;
            $pagando     = round((float) ($t['pagando'] ?? 0), 2);
            $descontando = round((float) ($t['descontando'] ?? 0), 2);

            if (!$codtitulo) {
                // Linha sintética = benefício (rubricas). Não gera movimento.
                $rubricas += $pagando;
                continue;
            }

            if ($pagando > 0) {
                // Título a crédito sendo pago → débito baixa o crédito.
                $creditos    += $pagando;
                $movimentos[] = ['codtitulo' => (int) $codtitulo, 'debito' => $pagando, 'credito' => null];
            }
            if ($descontando > 0) {
                // Título a débito (vale) sendo descontado → crédito baixa o débito.
                $debitos     += $descontando;
                $movimentos[] = ['codtitulo' => (int) $codtitulo, 'debito' => null, 'credito' => $descontando];
            }
        }

        $saldo = round($rubricas + $creditos - $debitos, 2);

        $agora    = Carbon::now();
        $dataForm = $data ? Carbon::parse($data)->toDateString() : $agora->toDateString();

        $acerto = new PeriodoColaboradorAcerto([
            'codperiodocolaborador' => $codperiodocolaborador,
            'data'                  => $dataForm,
            'forma'                 => $forma,
            'rubricas'              => round($rubricas, 2),
            'creditos'              => round($creditos, 2),
            'debitos'               => round($debitos, 2),
            'saldo'                 => $saldo,
            'observacao'            => $observacao ?: null,
        ]);
        $acerto->save();

        foreach ($movimentos as $m) {
            static::criarMovimento(
                $acerto->codperiodocolaboradoracerto,
                $m['codtitulo'],
                $m['debito'],
                $m['credito'],
                $dataForm,
                $agora
            );
        }

        $acerto->load(['MovimentoTituloS.Titulo', 'UsuarioCriacao']);

        return ['acerto' => $acerto];
    }

    protected static function criarMovimento(
        int $codperiodocolaboradoracerto,
        int $codtitulo,
        ?float $debito,
        ?float $credito,
        string $data,
        Carbon $agora,
        int $tipo = MovimentoTituloService::TIPO_RH
    ): void {
        $mov = new MovimentoTitulo([
            'codtipomovimentotitulo'      => $tipo,
            'codtitulo'                   => $codtitulo,
            'codperiodocolaboradoracerto' => $codperiodocolaboradoracerto,
            'codliquidacaotitulo'         => null,
            'codportador'                 => null,
            'debito'                      => $debito,
            'credito'                     => $credito,
            'historico'                   => 'Acerto RH',
            'transacao'                   => $data,
            'sistema'                     => $agora,
        ]);
        $mov->save();
    }

    // -------------------------------------------------------------------------
    // Inativar / Reativar (registro nunca é excluído; toggle reversível)
    // -------------------------------------------------------------------------

    public static function inativarAcerto(int $codperiodocolaboradoracerto): int
    {
        $acerto = static::acertoEditavel($codperiodocolaboradoracerto);
        if ($acerto->inativo) {
            throw new \Exception('Este acerto já está inativo.');
        }
        $qtd = static::ajustarBaixas($acerto, false); // desfaz as baixas
        $acerto->inativo = Carbon::now();
        $acerto->save();
        return $qtd;
    }

    /**
     * Quanto deste colaborador já foi para o cartão-benefício.
     *
     * Serve de aviso ao estornar um acerto: o dinheiro do cartão não volta, e
     * sem o acerto que o lastreava o colaborador fica com saldo negativo na
     * tela de recarga até alguém refazer a conta. Devolve
     * [em lote vivo, confirmado nos cartões].
     */
    public static function recargaJaEnviada(int $codperiodocolaborador): array
    {
        $r = DB::selectOne("
            SELECT
                COALESCE(SUM(i.valor) FILTER (WHERE b.inativo IS NULL), 0) AS vivo,
                COALESCE(SUM(i.valor) FILTER (WHERE b.status = :statusok), 0) AS confirmado
            FROM tblbeerecargaperiodocolaborador i
            JOIN tblbeerecarga b ON b.codbeerecarga = i.codbeerecarga
            WHERE i.codperiodocolaborador = :codpc
        ", ['codpc' => $codperiodocolaborador, 'statusok' => BeeRecarga::STATUS_OK]);

        return [(float) $r->vivo, (float) $r->confirmado];
    }

    public static function reativarAcerto(int $codperiodocolaboradoracerto): int
    {
        $acerto = static::acertoEditavel($codperiodocolaboradoracerto);
        if (!$acerto->inativo) {
            throw new \Exception('Este acerto já está ativo.');
        }
        $qtd = static::ajustarBaixas($acerto, true); // reaplica as baixas
        $acerto->inativo = null;
        $acerto->save();
        return $qtd;
    }

    protected static function acertoEditavel(int $codperiodocolaboradoracerto): PeriodoColaboradorAcerto
    {
        $acerto = PeriodoColaboradorAcerto::with(['MovimentoTituloS', 'PeriodoColaborador'])
            ->findOrFail($codperiodocolaboradoracerto);

        if (optional($acerto->PeriodoColaborador)->status === PeriodoService::STATUS_COLABORADOR_ENCERRADO) {
            throw new \Exception('Colaborador encerrado — reabra para editar o acerto.');
        }
        return $acerto;
    }

    /**
     * Leva a baixa de cada título do acerto ao alvo desejado (idempotente):
     *  - $ativar = true  → alvo = baixa ORIGINAL (movimentos tipo 601 do efetivar)
     *  - $ativar = false → alvo = 0 (desfaz)
     * A diferença (alvo − atual) vira um movimento de ajuste (tipo 930). Como o
     * "original" é sempre derivado só dos 601, o toggle é estável a N idas e voltas.
     */
    protected static function ajustarBaixas(PeriodoColaboradorAcerto $acerto, bool $ativar): int
    {
        $agora = Carbon::now();
        $hoje  = $agora->toDateString();
        $qtd   = 0;

        foreach ($acerto->MovimentoTituloS->groupBy('codtitulo') as $codtitulo => $movs) {
            if (!$codtitulo) {
                continue;
            }
            $original = 0.0;
            $atual    = 0.0;
            foreach ($movs as $m) {
                $net    = (float) ($m->debito ?? 0) - (float) ($m->credito ?? 0);
                $atual += $net;
                if ((int) $m->codtipomovimentotitulo === MovimentoTituloService::TIPO_RH) {
                    $original += $net;
                }
            }

            $alvo  = $ativar ? $original : 0.0;
            $delta = round($alvo - $atual, 2);
            if (abs($delta) < 0.01) {
                continue;
            }

            static::criarMovimento(
                $acerto->codperiodocolaboradoracerto,
                (int) $codtitulo,
                $delta > 0 ? $delta : null,
                $delta < 0 ? -$delta : null,
                $hoje,
                $agora,
                MovimentoTituloService::TIPO_ESTORNO_LIQUIDACAO
            );
            $qtd++;
        }

        return $qtd;
    }

    // -------------------------------------------------------------------------
    // Verificação
    // -------------------------------------------------------------------------

    public static function temAcertoAtivo(int $codperiodocolaborador): bool
    {
        return PeriodoColaboradorAcerto::where('codperiodocolaborador', $codperiodocolaborador)
            ->whereNull('inativo')
            ->exists();
    }

    /**
     * Inativa TODOS os eventos ativos do colaborador (botão "Estornar Acerto").
     */
    public static function estornarTodos(int $codperiodocolaborador): int
    {
        $codigos = PeriodoColaboradorAcerto::where('codperiodocolaborador', $codperiodocolaborador)
            ->whereNull('inativo')
            ->pluck('codperiodocolaboradoracerto');

        foreach ($codigos as $cod) {
            static::inativarAcerto($cod);
        }

        return $codigos->count();
    }
}

<?php

namespace Mg\Rh;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mg\Colaborador\ColaboradorCargo;
use Mg\Titulo\LiquidacaoTitulo;
use Mg\Titulo\MovimentoTitulo;
use Mg\Titulo\MovimentoTituloService;
use Mg\Portador\PortadorService;

class AcertoService
{
    // -------------------------------------------------------------------------
    // Listagem
    // -------------------------------------------------------------------------

    public static function listarAcertos(int $codperiodo, int $dias = 5): Collection
    {
        $periodo = Periodo::findOrFail($codperiodo);

        $pcs = PeriodoColaborador::where('codperiodo', $codperiodo)
            ->with([
                'Colaborador.Pessoa',
                'PeriodoColaboradorSetorS.Setor.UnidadeNegocio',
            ])
            ->get();

        // Liquidações ativas do período, agrupadas por codpessoa
        $liquidacoesPorPessoa = LiquidacaoTitulo::where('codperiodo', $codperiodo)
            ->whereNull('estornado')
            ->with('MovimentoTituloS')
            ->get()
            ->groupBy('codpessoa');

        $resultado = $pcs->map(function ($pc) use ($dias, $liquidacoesPorPessoa) {
            $codpessoa = $pc->Colaborador->codpessoa ?? null;

            $primeiroPcs = $pc->PeriodoColaboradorSetorS->first();
            $codunidadenegocio = $primeiroPcs?->Setor?->UnidadeNegocio?->codunidadenegocio ?? null;
            $unidade = $primeiroPcs?->Setor?->UnidadeNegocio?->descricao ?? null;

            $liquidacoesColaborador = $liquidacoesPorPessoa->get($codpessoa, collect());
            $efetivado = $liquidacoesColaborador->isNotEmpty();

            if ($efetivado) {
                [$creditos, $debitos, $financeiro, $folha] = static::valoresReaisLiquidacoes($liquidacoesColaborador);
                [$remanescente_valor, $remanescente_qtd]   = static::remanescente($codpessoa);
                $status_acerto                             = 'efetivado';
            } else {
                [$creditos, $debitos, $financeiro, $folha, $remanescente_valor, $remanescente_qtd] =
                    static::simularAcerto($codpessoa, $dias);
                $status_acerto = 'pendente';
            }

            return (object) [
                'codperiodocolaborador' => $pc->codperiodocolaborador,
                'codcolaborador'        => $pc->codcolaborador,
                'codpessoa'             => $codpessoa,
                'nome'                  => $pc->Colaborador?->Pessoa?->pessoa ?? '—',
                'status_periodo'        => $pc->status,
                'status_acerto'         => $status_acerto,
                'creditos'              => round($creditos, 2),
                'debitos'               => round($debitos, 2),
                'financeiro'            => round($financeiro, 2),
                'folha'                 => round($folha, 2),
                'remanescente_valor'    => round($remanescente_valor, 2),
                'remanescente_qtd'      => $remanescente_qtd,
                'codunidadenegocio'     => $codunidadenegocio,
                'unidade'               => $unidade,
            ];
        });

        return $resultado;
    }

    protected static function valoresReaisLiquidacoes(Collection $liquidacoes): array
    {
        $creditos   = 0;
        $debitos    = 0;
        $financeiro = 0;
        $folha      = 0;

        foreach ($liquidacoes as $liq) {
            $totalDebito  = $liq->MovimentoTituloS->sum('debito');
            $totalCredito = $liq->MovimentoTituloS->sum('credito');

            if ($liq->codportador == PortadorService::CAIXA) {
                // Débitos nos movimentos = o que a empresa pagou (créditos do colaborador)
                $creditos   += $totalDebito;
                // Créditos nos movimentos = o que foi compensado (débitos do colaborador)
                $debitos    += $totalCredito;
                $financeiro += max(0, $totalDebito - $totalCredito);
            } elseif ($liq->codportador == PortadorService::FOLHA) {
                // Créditos nos movimentos = débitos descontados em folha
                $debitos += $totalCredito;
                $folha   += $totalCredito;
            }
        }

        return [$creditos, $debitos, $financeiro, $folha];
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

    protected static function simularAcerto(int $codpessoa, int $dias): array
    {
        $titulos = DB::select("
            SELECT
                t.saldo,
                CASE
                    WHEN t.saldo < 0 AND t.vencimento <= CURRENT_DATE + CAST(:dias AS integer) THEN ABS(t.saldo)
                    ELSE 0
                END AS sugestao_pagando,
                CASE
                    WHEN t.saldo > 0 AND t.vencimento <= CURRENT_DATE + CAST(:dias2 AS integer) THEN t.saldo
                    ELSE 0
                END AS sugestao_descontando
            FROM tbltitulo t
            WHERE t.codpessoa = :codpessoa
              AND t.saldo != 0
        ", ['codpessoa' => $codpessoa, 'dias' => $dias, 'dias2' => $dias]);

        $creditos            = 0;
        $debitos             = 0;
        $remanescente_valor  = 0;
        $remanescente_qtd    = 0;

        foreach ($titulos as $t) {
            $creditos += $t->sugestao_pagando;
            $debitos  += $t->sugestao_descontando;

            // Remanescente: títulos não incluídos na sugestão
            if ($t->sugestao_pagando == 0 && $t->sugestao_descontando == 0 && $t->saldo != 0) {
                $remanescente_valor += abs($t->saldo);
                $remanescente_qtd++;
            }
        }

        $resultado  = $creditos - $debitos;
        $financeiro = max(0, $resultado);
        $folha      = max(0, -$resultado);

        return [$creditos, $debitos, $financeiro, $folha, $remanescente_valor, $remanescente_qtd];
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

        // Títulos com saldo != 0
        $titulos = DB::select("
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
            ORDER BY t.vencimento, t.saldo, t.codtitulo
        ", [
            'codpessoa' => $colaborador->codpessoa,
            'dias'      => $dias,
            'dias2'     => $dias,
        ]);

        return [
            'colaborador' => [
                'codperiodocolaborador'   => $pc->codperiodocolaborador,
                'codpessoa'               => $colaborador->codpessoa,
                'nome'                    => $pessoa->pessoa ?? '—',
                'cargo'                   => $cargo?->Cargo?->cargo ?? null,
                'tempo_casa'              => $tempoCasa,
                'salario'                 => $salario ? (float) $salario : null,
                'percentual_max_desconto' => (float) ($periodo->percentualmaxdesconto ?? 30),
            ],
            'titulos' => $titulos,
        ];
    }

    // -------------------------------------------------------------------------
    // Efetivação
    // -------------------------------------------------------------------------

    public static function efetivar(int $codperiodocolaborador, array $titulos, ?string $observacao): array
    {
        $pc = PeriodoColaborador::with(['Colaborador', 'Periodo'])
            ->findOrFail($codperiodocolaborador);

        if ($pc->status !== PeriodoService::STATUS_COLABORADOR_ENCERRADO) {
            throw new \Exception('Somente colaboradores com status E (encerrado) podem ter acerto efetivado.');
        }

        $codpessoa = $pc->Colaborador->codpessoa;
        $codperiodo = $pc->codperiodo;

        if (static::verificarLiquidacaoAtiva($codperiodo, $codpessoa)) {
            throw new \Exception('Já existe um acerto efetivado para este colaborador neste período.');
        }

        // Buscar saldos atuais dos títulos para validação
        $codtitulos   = array_column($titulos, 'codtitulo');
        $saldosAtuais = DB::table('tbltitulo')
            ->whereIn('codtitulo', $codtitulos)
            ->pluck('saldo', 'codtitulo');

        // Separar em créditos (saldo < 0 → pagando) e débitos (saldo > 0 → descontando)
        $creditos  = []; // [codtitulo => pagando]
        $debitos   = []; // [codtitulo => descontando]

        foreach ($titulos as $t) {
            $codtitulo   = (int) $t['codtitulo'];
            $pagando     = (float) ($t['pagando'] ?? 0);
            $descontando = (float) ($t['descontando'] ?? 0);

            if ($pagando > 0) {
                $creditos[$codtitulo] = $pagando;
            }
            if ($descontando > 0) {
                $debitos[$codtitulo] = $descontando;
            }
        }

        $totalPagando     = array_sum($creditos);
        $totalDescontando = array_sum($debitos);
        $resultado        = $totalPagando - $totalDescontando;

        // Observação
        $periodo    = $pc->Periodo;
        $obsLiquidacao = 'RH Período de '
            . $periodo->periodoinicial->format('d/m/Y')
            . ' a '
            . $periodo->periodofinal->format('d/m/Y');
        if (!empty($observacao)) {
            $obsLiquidacao .= "\n" . $observacao;
        }

        $agora   = Carbon::now();
        $hoje    = $agora->toDateString();
        $usuario = Auth::user()->codusuario;

        $liquidacoesCriadas = [];

        if ($resultado >= 0) {
            // Cenário A ou C: 1 liquidação portador CAIXA
            if (!empty($creditos) || !empty($debitos)) {
                $liq = static::criarLiquidacao($codpessoa, $codperiodo, PortadorService::CAIXA, $obsLiquidacao, $usuario, $hoje, $agora, $totalPagando, $totalDescontando);

                foreach ($creditos as $codtitulo => $valor) {
                    static::criarMovimento($liq->codliquidacaotitulo, $codtitulo, PortadorService::CAIXA, $valor, null, $hoje, $agora);
                }
                foreach ($debitos as $codtitulo => $valor) {
                    static::criarMovimento($liq->codliquidacaotitulo, $codtitulo, PortadorService::CAIXA, null, $valor, $hoje, $agora);
                }

                $liquidacoesCriadas[] = [
                    'codliquidacaotitulo' => $liq->codliquidacaotitulo,
                    'portador'            => 'Caixa Financeiro',
                    'total'               => round($resultado, 2),
                ];
            }
        } else {
            // Cenário D: até 2 liquidações
            $restanteLiq2 = [];

            // Liq 1: portador CAIXA — só se tem créditos pra compensar
            if (!empty($creditos)) {
                $capLiq1      = $totalPagando;
                $creditadoLiq1 = 0;

                $liq1 = static::criarLiquidacao($codpessoa, $codperiodo, PortadorService::CAIXA, $obsLiquidacao, $usuario, $hoje, $agora, $totalPagando, 0);

                foreach ($creditos as $codtitulo => $valor) {
                    static::criarMovimento($liq1->codliquidacaotitulo, $codtitulo, PortadorService::CAIXA, $valor, null, $hoje, $agora);
                }

                foreach ($debitos as $codtitulo => $valor) {
                    if ($capLiq1 <= 0) {
                        $restanteLiq2[$codtitulo] = $valor;
                        continue;
                    }
                    $valorLiq1 = min($valor, $capLiq1);
                    static::criarMovimento($liq1->codliquidacaotitulo, $codtitulo, PortadorService::CAIXA, null, $valorLiq1, $hoje, $agora);
                    $capLiq1      -= $valorLiq1;
                    $creditadoLiq1 += $valorLiq1;

                    $sobra = $valor - $valorLiq1;
                    if ($sobra > 0) {
                        $restanteLiq2[$codtitulo] = $sobra;
                    }
                }

                $liq1->credito = $creditadoLiq1;
                $liq1->save();

                $liquidacoesCriadas[] = [
                    'codliquidacaotitulo' => $liq1->codliquidacaotitulo,
                    'portador'            => 'Caixa Financeiro',
                    'total'               => round($totalPagando, 2),
                ];
            } else {
                $restanteLiq2 = $debitos;
            }

            // Liq 2: portador FOLHA — débito restante
            if (!empty($restanteLiq2)) {
                $folhaTotal = array_sum($restanteLiq2);
                $liq2 = static::criarLiquidacao($codpessoa, $codperiodo, PortadorService::FOLHA, $obsLiquidacao, $usuario, $hoje, $agora, 0, $folhaTotal);

                foreach ($restanteLiq2 as $codtitulo => $valor) {
                    static::criarMovimento($liq2->codliquidacaotitulo, $codtitulo, PortadorService::FOLHA, null, $valor, $hoje, $agora);
                }

                $liquidacoesCriadas[] = [
                    'codliquidacaotitulo' => $liq2->codliquidacaotitulo,
                    'portador'            => 'Acerto Folha Salarial',
                    'total'               => round($folhaTotal, 2),
                ];
            }
        }

        return [
            'status'      => 'efetivado',
            'liquidacoes' => $liquidacoesCriadas,
            'financeiro'  => round(max(0, $resultado), 2),
            'folha'       => round(max(0, -$resultado), 2),
        ];
    }

    protected static function criarLiquidacao(
        int $codpessoa,
        int $codperiodo,
        int $codportador,
        string $observacao,
        int $codusuario,
        string $hoje,
        Carbon $agora,
        float $debito = 0,
        float $credito = 0
    ): LiquidacaoTitulo {
        $liq = new LiquidacaoTitulo([
            'transacao'    => $hoje,
            'sistema'      => $agora,
            'codportador'  => $codportador,
            'observacao'   => $observacao,
            'codusuario'   => $codusuario,
            'codpessoa'    => $codpessoa,
            'codperiodo'   => $codperiodo,
            'tipo'         => null,
            'codpdv'       => null,
            'debito'       => $debito,
            'credito'      => $credito,
        ]);
        $liq->save();
        return $liq;
    }

    protected static function criarMovimento(
        int $codliquidacaotitulo,
        int $codtitulo,
        int $codportador,
        ?float $debito,
        ?float $credito,
        string $hoje,
        Carbon $agora
    ): void {
        $mov = new MovimentoTitulo([
            'codtipomovimentotitulo' => MovimentoTituloService::TIPO_RH,
            'codtitulo'              => $codtitulo,
            'codportador'            => $codportador,
            'debito'                 => $debito,
            'credito'                => $credito,
            'historico'              => null,
            'transacao'              => $hoje,
            'sistema'                => $agora,
            'codliquidacaotitulo'    => $codliquidacaotitulo,
        ]);
        $mov->save();
    }

    // -------------------------------------------------------------------------
    // Estorno
    // -------------------------------------------------------------------------

    public static function estornar(int $codperiodocolaborador): int
    {
        $pc = PeriodoColaborador::with('Colaborador')->findOrFail($codperiodocolaborador);
        $codpessoa  = $pc->Colaborador->codpessoa;
        $codperiodo = $pc->codperiodo;
        $usuario    = Auth::user()->codusuario;

        $liquidacoes = LiquidacaoTitulo::where('codperiodo', $codperiodo)
            ->where('codpessoa', $codpessoa)
            ->whereNull('estornado')
            ->with('MovimentoTituloS')
            ->get();

        if ($liquidacoes->isEmpty()) {
            throw new \Exception('Nenhuma liquidação ativa encontrada para este colaborador/período.');
        }

        $agora = Carbon::now();

        foreach ($liquidacoes as $liq) {
            foreach ($liq->MovimentoTituloS as $mov) {
                MovimentoTituloService::estornar($mov);
            }

            $liq->estornado         = $agora;
            $liq->codusuarioestorno = $usuario;
            $liq->save();
        }

        return $liquidacoes->count();
    }

    // -------------------------------------------------------------------------
    // Verificação
    // -------------------------------------------------------------------------

    public static function verificarLiquidacaoAtiva(int $codperiodo, int $codpessoa): bool
    {
        return LiquidacaoTitulo::where('codperiodo', $codperiodo)
            ->where('codpessoa', $codpessoa)
            ->whereNull('estornado')
            ->exists();
    }
}

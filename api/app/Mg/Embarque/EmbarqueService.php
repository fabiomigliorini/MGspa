<?php

namespace Mg\Embarque;

use Mg\MgService;
use Mg\Contrato\Contrato;
use Mg\Cultura\TabelaDesconto;
use Illuminate\Validation\ValidationException;

class EmbarqueService extends MgService
{
    const WITH = [
        'EmbarqueContratoS.Contrato.Pessoa',
        'EmbarqueContratoS.Contrato.Cultura',
        'EmbarqueOrigemS.Plantio.Talhao',
    ];

    const ETAPAS = ['PATIO', 'TARA', 'CLASSIFICACAO', 'BRUTO', 'FISCAL', 'DESPACHADO'];

    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = Embarque::query()->with(static::WITH);

        if (!empty($filter['codembarque'])) {
            $qry->where('codembarque', $filter['codembarque']);
        }
        if (!empty($filter['uuid'])) {
            $qry->where('uuid', $filter['uuid']);
        }
        if (!empty($filter['etapa'])) {
            $qry->where('etapa', $filter['etapa']);
        }
        if (!empty($filter['codcontrato'])) {
            $qry->whereHas('EmbarqueContratoS', fn ($q) => $q->where('codcontrato', $filter['codcontrato']));
        }
        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        $qry = self::qryOrdem($qry, $sort ?: ['-data']);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    /**
     * Upsert por uuid (offline). Grava o embarque + contratos (rateio/NF) +
     * origens (silo/talhao). Servidor recalcula peso liquido e descontos.
     */
    public static function sincronizar(array $data): Embarque
    {
        $embarque = Embarque::firstOrNew(['uuid' => $data['uuid']]);
        $embarque->fill($data);
        static::calcular($embarque, $data);
        // Valida ANTES de salvar (over-load do contrato + fechamento/sanidade).
        // codembarque ja existe aqui se for edicao (firstOrNew achou pelo uuid),
        // permitindo excluir o proprio embarque do total ja carregado.
        static::validar($embarque, $data);
        $embarque->save();
        static::sincronizarContratos($embarque, $data['contratos'] ?? []);
        static::sincronizarOrigens($embarque, $data['origens'] ?? []);
        return $embarque->fresh(static::WITH);
    }

    /**
     * Validacoes de quantidade do embarque (autoridade do servidor — o front
     * tem guards best-effort, mas o cache offline pode estar defasado).
     */
    protected static function validar(Embarque $embarque, array $data): void
    {
        $contratos = $data['contratos'] ?? [];
        static::validarCarregamento($embarque, $contratos);

        // Fechamento do rateio + sanidade fisica: so cobra nas etapas finais
        // (emitir NF / despachar). Antes disso aceita parcial (kanban em curso).
        if (in_array($embarque->etapa, ['FISCAL', 'DESPACHADO'], true)) {
            $liq = (float) $embarque->pesoliquido;
            if ($liq <= 0) {
                throw ValidationException::withMessages([
                    'pesoliquido' => 'Peso liquido invalido (bruto - tara deve ser > 0) para emitir NF / despachar.',
                ]);
            }
            $soma = array_sum(array_map(fn ($c) => (float) ($c['quantidade'] ?? 0), $contratos));
            if (abs($soma - $liq) > 1) {
                throw ValidationException::withMessages([
                    'contratos' => "A soma dos contratos (" . round($soma) . " kg) deve fechar com o "
                        . "liquido (" . round($liq) . " kg) para emitir NF / despachar.",
                ]);
            }

            // Fechamento das origens (silo/talhao): se houver origens lancadas,
            // a soma delas tambem tem que bater com o liquido. Sem origens, nao
            // forca (rastreio de origem e opcional no fluxo).
            $origens = $data['origens'] ?? [];
            if ($origens) {
                $somaOrig = array_sum(array_map(fn ($o) => (float) ($o['quantidade'] ?? 0), $origens));
                if (abs($somaOrig - $liq) > 1) {
                    throw ValidationException::withMessages([
                        'origens' => "A soma das origens (" . round($somaOrig) . " kg) deve fechar com o "
                            . "liquido (" . round($liq) . " kg) para emitir NF / despachar.",
                    ]);
                }
            }
        }
    }

    /**
     * Bloqueio de over-load: um embarque nao pode levar o total carregado de um
     * contrato com teto acima do contratado. Contrato com semlimite = true
     * (leva o saldo do silo) pula a checagem.
     */
    protected static function validarCarregamento(Embarque $embarque, array $contratos): void
    {
        // Agrupa kg por contrato (uma carga pode ratear o mesmo contrato em 2+ linhas).
        $kgPorContrato = [];
        foreach ($contratos as $c) {
            if (empty($c['codcontrato'])) {
                continue;
            }
            $cod = (int) $c['codcontrato'];
            $kgPorContrato[$cod] = ($kgPorContrato[$cod] ?? 0) + (float) ($c['quantidade'] ?? 0);
        }
        if (!$kgPorContrato) {
            return;
        }

        $contratosDb = Contrato::with('Cultura')
            ->whereIn('codcontrato', array_keys($kgPorContrato))
            ->get()
            ->keyBy('codcontrato');

        foreach ($kgPorContrato as $cod => $estaCargaKg) {
            $contrato = $contratosDb->get($cod);
            if (!$contrato || $contrato->semlimite) {
                continue; // sem teto: leva o saldo do silo
            }
            $pesosaca = (float) ($contrato->Cultura->pesosaca ?? 60) ?: 60;
            $contratadokg = (float) $contrato->quantidade * $pesosaca;

            // kg ja embarcado em OUTROS embarques ATIVOS (exclui o proprio ao
            // editar; embarque inativado nao conta como carregado).
            $jaOutros = (float) EmbarqueContrato::where('codcontrato', $cod)
                ->whereHas('Embarque', fn ($e) => $e->whereNull('inativo'))
                ->when(
                    $embarque->codembarque,
                    fn ($q) => $q->where('codembarque', '!=', $embarque->codembarque)
                )
                ->sum('quantidade');

            if ($jaOutros + $estaCargaKg > $contratadokg + 1) {
                $saldo = max(0, $contratadokg - $jaOutros);
                throw ValidationException::withMessages([
                    'contratos' => "Contrato {$contrato->contrato}: carregamento excede o contratado ("
                        . round($contratadokg) . " kg). Saldo disponivel: " . round($saldo) . " kg.",
                ]);
            }
        }
    }

    protected static function sincronizarContratos(Embarque $embarque, array $contratos): void
    {
        EmbarqueContrato::where('codembarque', $embarque->codembarque)->delete();
        foreach ($contratos as $c) {
            if (empty($c['codcontrato'])) {
                continue;
            }
            $ec = new EmbarqueContrato();
            $ec->codembarque = $embarque->codembarque;
            $ec->codcontrato = $c['codcontrato'];
            $ec->quantidade = $c['quantidade'] ?? null;
            $ec->numeronf = $c['numeronf'] ?? null;
            $ec->valornf = $c['valornf'] ?? null;
            $ec->chavenf = $c['chavenf'] ?? null;
            $ec->save();
        }
    }

    protected static function sincronizarOrigens(Embarque $embarque, array $origens): void
    {
        EmbarqueOrigem::where('codembarque', $embarque->codembarque)->delete();
        foreach ($origens as $o) {
            if (empty($o['tipo'])) {
                continue;
            }
            $eo = new EmbarqueOrigem();
            $eo->codembarque = $embarque->codembarque;
            $eo->tipo = $o['tipo'];
            $eo->codplantio = $o['codplantio'] ?? null;
            $eo->quantidade = $o['quantidade'] ?? null;
            $eo->save();
        }
    }

    public static function calcular(Embarque $e, array $data): void
    {
        if ($e->pesobruto !== null && $e->pesotara !== null) {
            $e->pesoliquido = round(((float) $e->pesobruto) - ((float) $e->pesotara), 3);
        } else {
            $e->pesoliquido = null;
        }

        $liq = $e->pesoliquido;
        if ($liq === null) {
            $e->descontoumidade = null;
            $e->descontoimpureza = null;
            $e->descontoavariados = null;
            $e->pesoliquidoseco = null;
            return;
        }

        $codcultura = static::codCultura($data['contratos'] ?? []);
        $e->descontoumidade = static::descontoKg($codcultura, 'UMIDADE', $e->umidade, $liq);
        $e->descontoimpureza = static::descontoKg($codcultura, 'IMPUREZA', $e->impureza, $liq);
        $e->descontoavariados = static::descontoKg($codcultura, 'AVARIADOS', $e->avariados, $liq);
        $e->pesoliquidoseco = round(
            $liq - ((float) $e->descontoumidade) - ((float) $e->descontoimpureza) - ((float) $e->descontoavariados),
            3
        );
    }

    protected static function codCultura(array $contratos): ?int
    {
        foreach ($contratos as $c) {
            if (!empty($c['codcontrato'])) {
                $ct = Contrato::find($c['codcontrato']);
                if ($ct) {
                    return $ct->codcultura;
                }
            }
        }
        return null;
    }

    public static function descontoKg(?int $codcultura, string $tipo, $leitura, float $pesoliquido): ?float
    {
        if (empty($codcultura) || $leitura === null || $leitura === '') {
            return null;
        }
        $faixa = TabelaDesconto::ativo()
            ->where('codcultura', $codcultura)
            ->where('tipo', $tipo)
            ->where('faixainicio', '<=', $leitura)
            ->where('faixafim', '>=', $leitura)
            ->orderBy('faixainicio', 'desc')
            ->first();
        if (!$faixa) {
            return 0.0;
        }
        return round($pesoliquido * ((float) $faixa->percentualdesconto / 100), 3);
    }
}

<?php

namespace Mg\Embarque;

use Mg\MgService;
use Mg\Contrato\Contrato;
use Mg\Cultura\TabelaDesconto;

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
        $embarque->save();
        static::sincronizarContratos($embarque, $data['contratos'] ?? []);
        static::sincronizarOrigens($embarque, $data['origens'] ?? []);
        return $embarque->fresh(static::WITH);
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

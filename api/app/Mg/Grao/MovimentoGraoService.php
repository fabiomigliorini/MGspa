<?php

namespace Mg\Grao;

use Mg\MgService;
use Mg\Contrato\Contrato;
use Mg\Fazenda\Plantio;

/**
 * Extrato/razao de grao. Saldo de qualquer conta = SUM(liquido). Os movimentos
 * automaticos sao gerados pela carga (CargaService); aqui ficam a consulta do
 * extrato, os saldos e os ajustes MANUAIS (comerciais), que o usuario gerencia.
 */
class MovimentoGraoService extends MgService
{
    const WITH = [
        'Carga',
        'Plantio.Talhao',
        'UnidadeArmazenadora',
        'Contrato.Pessoa',
    ];

    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = MovimentoGrao::query()->with(static::WITH);

        if (!empty($filter['contatipo'])) {
            $qry->where('contatipo', $filter['contatipo']);
        }
        if (!empty($filter['codcontrato'])) {
            $qry->where('codcontrato', $filter['codcontrato']);
        }
        if (!empty($filter['codunidadearmazenadora'])) {
            $qry->where('codunidadearmazenadora', $filter['codunidadearmazenadora']);
        }
        if (!empty($filter['codplantio'])) {
            $qry->where('codplantio', $filter['codplantio']);
        }
        if (!empty($filter['codsafra'])) {
            $qry->where('codsafra', $filter['codsafra']);
        }
        if (!empty($filter['codcarga'])) {
            $qry->where('codcarga', $filter['codcarga']);
        }
        if (isset($filter['manual']) && $filter['manual'] !== '') {
            $qry->where('manual', (bool) $filter['manual']);
        }
        // So movimentos ativos contam (manual inativado = estornado).
        $qry->whereNull('inativo');

        $qry = self::qryOrdem($qry, $sort ?: ['-data', '-codmovimentograo']);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    /** Saldo (kg liquido) de uma conta. */
    public static function saldo(string $contatipo, int $cod, ?int $codsafra = null): float
    {
        $col = match ($contatipo) {
            'PLANTIO' => 'codplantio',
            'UNIDADE' => 'codunidadearmazenadora',
            'CONTRATO' => 'codcontrato',
            default => null,
        };
        if ($col === null) {
            return 0.0;
        }
        return (float) MovimentoGrao::where('contatipo', $contatipo)
            ->where($col, $cod)
            ->whereNull('inativo')
            ->when($codsafra, fn ($q) => $q->where('codsafra', $codsafra))
            ->sum('liquido');
    }

    /**
     * Estoque depositado por unidade armazenadora (silo proprio + terceiro + silo
     * bag). Opcionalmente filtrado por safra. Retorna a unidade + saldo (kg/sc).
     */
    public static function saldosUnidades(?int $codsafra = null): array
    {
        $unidades = UnidadeArmazenadora::orderBy('unidadearmazenadora')->get();
        $ret = [];
        foreach ($unidades as $u) {
            $saldokg = static::saldo('UNIDADE', $u->codunidadearmazenadora, $codsafra);
            $ret[] = [
                'codunidadearmazenadora' => (int) $u->codunidadearmazenadora,
                'unidadearmazenadora' => $u->unidadearmazenadora,
                'tipo' => $u->tipo,
                'inativo' => $u->inativo,
                'saldokg' => round($saldokg, 3),
            ];
        }
        return $ret;
    }

    /**
     * Lanca um ajuste MANUAL (comercial) direto no extrato. Nunca tocado pelo
     * recalc. O operador informa quantidade POSITIVA; o sinal vem do papel, pela
     * mesma regra do automatico (CargaService::sinal): retirada de silo baixa.
     * Retirada de silo nao tem desconto (D9): liquido = -quantidade.
     */
    public static function lancarManual(array $data): MovimentoGrao
    {
        $mov = new MovimentoGrao();
        $mov->fill($data);
        $mov->codcarga = null;
        $mov->manual = true;
        if (empty($mov->data)) {
            $mov->data = now();
        }
        // So a FK do tipo de conta escolhido vale.
        foreach (['PLANTIO' => 'codplantio', 'UNIDADE' => 'codunidadearmazenadora', 'CONTRATO' => 'codcontrato'] as $tipo => $fk) {
            if ($mov->contatipo !== $tipo) {
                $mov->$fk = null;
            }
        }
        $retirada = $mov->contatipo === 'UNIDADE' && $mov->papel === 'ORIGEM';
        $bruto = round((float) ($data['bruto'] ?? $data['liquido'] ?? 0), 3);
        $desconto = $retirada ? 0.0 : round((float) ($data['desconto'] ?? 0), 3);
        $sinal = CargaService::sinal($mov->contatipo, $mov->papel);
        $mov->bruto = $bruto * $sinal;
        $mov->desconto = $desconto * $sinal;
        $mov->liquido = round(($bruto - $desconto) * $sinal, 3);
        // codsafra ausente: infere do contrato/plantio p/ o ajuste aparecer nos
        // KPIs por safra (estoque/entregue). UNIDADE sem safra fica global.
        if (empty($mov->codsafra)) {
            if ($mov->contatipo === 'CONTRATO' && $mov->codcontrato) {
                $mov->codsafra = optional(Contrato::find($mov->codcontrato))->codsafra;
            } elseif ($mov->contatipo === 'PLANTIO' && $mov->codplantio) {
                $mov->codsafra = optional(Plantio::find($mov->codplantio))->codsafra;
            }
        }
        $mov->save();
        return $mov->fresh(static::WITH);
    }
}

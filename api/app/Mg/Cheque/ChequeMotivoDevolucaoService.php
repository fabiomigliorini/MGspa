<?php

namespace Mg\Cheque;

use Illuminate\Database\QueryException;
use Mg\MgService;
use RuntimeException;

class ChequeMotivoDevolucaoService extends MgService
{
    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = ChequeMotivoDevolucao::query();

        if (!empty($filter['codchequemotivodevolucao'])) {
            $qry->where('codchequemotivodevolucao', $filter['codchequemotivodevolucao']);
        }

        if (!empty($filter['numero'])) {
            $qry->where('numero', $filter['numero']);
        }

        if (!empty($filter['chequemotivodevolucao'])) {
            $qry->palavras('chequemotivodevolucao', $filter['chequemotivodevolucao']);
        }

        if (empty($sort)) {
            $qry->orderBy('numero');
        }

        $qry = self::qryOrdem($qry, $sort);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    public static function autocompletar(array $params): array
    {
        $qry = static::pesquisar($params)
            ->select('codchequemotivodevolucao', 'numero', 'chequemotivodevolucao')
            ->orderBy('numero')
            ->take(50);
        $ret = [];
        foreach ($qry->get() as $item) {
            $ret[] = [
                'label' => $item->numero . ' - ' . $item->chequemotivodevolucao,
                'value' => $item->codchequemotivodevolucao,
                'codchequemotivodevolucao' => $item->codchequemotivodevolucao,
                'numero' => $item->numero,
                'chequemotivodevolucao' => $item->chequemotivodevolucao,
            ];
        }
        return $ret;
    }

    public static function excluir(ChequeMotivoDevolucao $model): void
    {
        try {
            $model->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) === '23503') {
                throw new RuntimeException('Motivo de devolução em uso, não pode ser excluído.');
            }
            throw $e;
        }
    }
}

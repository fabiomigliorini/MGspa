<?php

namespace Mg\Pessoa;

use Mg\MgService;
use Mg\Cidade\Cidade;
use DB;

class PessoaService extends MgService
{
    /**
     * Busca Autocomplete Quasar
     */
    public static function autocomplete($params)
    {
        $nome = $params['pessoa'];
        $qry = Pessoa::query();
        $qry->select('codpessoa', 'pessoa', 'fantasia', 'cnpj', 'inativo', 'fisica');
        $qry->where('pessoa', 'ilike', $nome.'%')->orWhere('fantasia', 'ilike', $nome.'%');
        $ret = $qry->limit(50)->get();
        return $ret;
    }


    public static function pesquisar(array $filter = null, array $sort = null, array $fields = null)
    {
        $qry = Pessoa::query();

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        if (!empty($filter['filial'])) {
            $qry->palavras('filial', $filter['filial']);
        }

        $qry = self::qryOrdem($qry, $sort);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    public static function buscarPorCnpjIe ($cnpj, $ie)
    {
        $ie = (int) numeroLimpo($ie);
        return Pessoa::where('cnpj', $cnpj)->where(DB::raw("cast(regexp_replace(ie, '[^0-9]+', '', 'g') as numeric)"), $ie)->first();
    }

}

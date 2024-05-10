<?php

namespace Mg\Filial;

use DB;

use Mg\MgService;
use Mg\Pessoa\Pessoa;

class FilialService extends MgService
{

    public static function pesquisar(array $filter = null, array $sort = null, array $fields = null)
    {
        $qry = Filial::query();

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


    public static function buscarPorCnpjIe ($cnpj, $ie, $primeiroComDfeHabilitada = true)
    {
        // Se nao tem IE
        if (empty($ie)) {
            $sql = "
                SELECT codpessoa
                FROM tblpessoa
                WHERE cnpj = :cnpj
                AND ie is null
            ";
            $codpessoas = collect(DB::select($sql, [
                'cnpj' => $cnpj
            ]))->pluck('codpessoa');

        // Se tem IE
        } else {
            $ie = (int) numeroLimpo($ie);
            $sql = "
                SELECT codpessoa
                FROM tblpessoa
                WHERE cnpj = :cnpj
                AND cast(regexp_replace(ie, '[^0-9]+', '', 'g') as numeric) = :ie
            ";
            $codpessoas = collect(DB::select($sql, [
                'cnpj' => $cnpj,
                'ie' => $ie
            ]))->pluck('codpessoa');
        }

        $qry = Filial::whereIn('codpessoa', $codpessoas);
        if ($primeiroComDfeHabilitada) {
            $qry = $qry->orderBy('dfe', 'desc');
        }
        
        return $qry->first();
    }

}

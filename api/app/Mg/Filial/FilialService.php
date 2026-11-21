<?php

namespace Mg\Filial;

use Illuminate\Support\Facades\DB;
use Mg\MgService;

class FilialService extends MgService
{
    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = Filial::with(['Pessoa', 'Empresa']);

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        if (!empty($filter['filial'])) {
            $qry->palavras('filial', $filter['filial']);
        }

        if (!empty($filter['codempresa'])) {
            $qry->where('codempresa', $filter['codempresa']);
        }

        $qry = self::qryOrdem($qry, $sort);
        if (empty($sort)) {
            $qry->orderBy('codfilial', 'asc');
        }
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    // $cnpj é o CNPJ (pode ter letras) ou o CPF da filial de pessoa física, com ou sem
    // pontuação, como vem do XML (TASK-283)
    public static function buscarPorCnpjIe($cnpj, ?string $ie, bool $primeiroComDfeHabilitada = true)
    {
        $cnpj = cnpjCpfLimpo($cnpj);
        if ($cnpj === null) {
            return null;
        }
        if (empty($ie)) {
            $sql = "SELECT codpessoa FROM tblpessoa WHERE cnpj = :cnpj AND ie IS NULL";
            $params = ['cnpj' => $cnpj];
        } else {
            $ie = (int) preg_replace('/\D+/', '', $ie);
            $sql = "
                SELECT codpessoa FROM tblpessoa
                WHERE cnpj = :cnpj
                AND cast(regexp_replace(ie, '[^0-9]+', '', 'g') as numeric) = :ie
            ";
            $params = ['cnpj' => $cnpj, 'ie' => $ie];
        }
        $codpessoas = collect(DB::select($sql, $params))->pluck('codpessoa');

        $qry = Filial::whereIn('codpessoa', $codpessoas);
        if ($primeiroComDfeHabilitada) {
            $qry = $qry->orderBy('dfe', 'desc');
        }
        return $qry->first();
    }
}

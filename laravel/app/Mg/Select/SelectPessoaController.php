<?php

namespace Mg\Select;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;

use Mg\Cidade\Cidade;

class SelectPessoaController extends Controller
{
    public static function index(Request $request)
    {
        // busca Pessoas
        $sql = "
            select 
                p.codpessoa,
                p.fisica,
                p.fantasia,
                p.pessoa,
                p.cnpj,
                p.ie,
                p.endereco,
                p.numero,
                p.complemento,
                p.bairro,
                c.cidade,
                e.sigla as uf,
                p.vendedor,
                p.inativo
            from tblpessoa p
            left join tblcidade c on (c.codcidade = p.codcidade)
            left join tblestado e on (e.codestado = c.codestado)
        ";

        $params = [];
        $where = 'where';
        if (!empty($request->codpessoa)) {
            $sql .= "
                {$where} p.codpessoa = :codpessoa
            ";
            $where = 'and';
            $params['codpessoa'] = $request->codpessoa;
        }
        if ($request->somenteAtivos) {
            $sql .= "
                {$where} p.inativo is null
            ";
            $where = 'and';
        }
        if ($request->somenteVendedores) {
            $sql .= "
                {$where} p.vendedor = true
            ";
            $where = 'and';
        }
        if (!empty($request->pessoa)) {
            $busca = trim(removeAcentos($request->pessoa));
            $letras = preg_replace("/[^A-Za-z]/", '', $busca);
            $numeros = preg_replace("/[^\d]/", '', $busca);

            // decide se vai buscar pelo CNPJ/codigo ou pelo nome
            if (strlen($letras) == 0 && strlen($numeros) > 0) {
                $sql .= "
                    {$where} (to_char(p.cnpj, case when p.fisica then 'FM00000000000' else 'FM00000000000000' end) ilike :cnpj or to_char(p.codpessoa, 'FM00000000') ilike :codpessoa)
                ";
                $params['cnpj'] = "%{$numeros}%";
                $params['codpessoa'] = "{$numeros}%";
                $where = 'and';
            } else {
                $palavras = preg_replace('/\s+/', ' ', $busca);
                $palavras = explode(' ', $palavras);
                foreach ($palavras as $i => $palavra) {
                    $sql .= "
                        {$where} p.fantasia || ' ' || p.pessoa ilike :palavra{$i}
                    ";
                    $params["palavra{$i}"] = "%{$palavra}%";
                    $where = 'and';
                }
            }
        }
        $sql .= '
            order by p.fantasia, p.pessoa, p.codpessoa
            limit 100
        ';
        $ret = DB::select($sql, $params);

        // retorna
        return $ret;
    }
}

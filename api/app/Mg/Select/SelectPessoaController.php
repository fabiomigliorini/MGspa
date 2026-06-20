<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectPessoaController extends Controller
{
    public static function index(Request $request)
    {
        $page = (int) $request->page > 0 ? (int) $request->page : 1;
        $offset = ($page - 1) * 20;

        $sql = '
            select
                p.codpessoa, p.fisica, p.fantasia, p.pessoa, p.cnpj, p.ie,
                p.endereco, p.numero, p.complemento, p.bairro,
                p.codgrupoeconomico, ge.grupoeconomico,
                c.cidade, e.sigla as uf,
                p.vendedor, p.inativo
            from tblpessoa p
            left join tblgrupoeconomico ge on (ge.codgrupoeconomico = p.codgrupoeconomico)
            left join tblcidade c on (c.codcidade = p.codcidade)
            left join tblestado e on (e.codestado = c.codestado)
        ';

        $params = [];
        $where = 'where';

        if ($request->somenteAtivos) {
            $sql .= " {$where} p.inativo is null";
            $where = 'and';
        }

        if ($request->somenteVendedores) {
            $sql .= " {$where} p.vendedor = true";
            $where = 'and';
        }

        if (!empty($request->busca)) {
            $busca = trim(removeAcentos($request->busca));
            $letras = preg_replace('/[^A-Za-z]/', '', $busca);
            $numeros = preg_replace('/[^\d]/', '', $busca);

            if (strlen($letras) == 0 && strlen($numeros) > 0) {
                $sql .= " {$where} (to_char(p.cnpj, case when p.fisica then 'FM00000000000' else 'FM00000000000000' end) ilike :cnpj or to_char(p.codpessoa, 'FM00000000') ilike :codpessoa)";
                $params['cnpj'] = "%{$numeros}%";
                $params['codpessoa'] = "{$numeros}%";
            } else {
                $palavras = explode(' ', preg_replace('/\s+/', ' ', $busca));
                foreach ($palavras as $i => $palavra) {
                    $sql .= " {$where} p.fantasia || ' ' || p.pessoa ilike :palavra{$i}";
                    $params["palavra{$i}"] = "%{$palavra}%";
                    $where = 'and';
                }
            }
        }

        $sql .= ' order by p.fantasia, p.pessoa, p.codpessoa limit 20 offset ' . $offset;
        return DB::select($sql, $params);
    }

    public static function show($id)
    {
        $sql = '
            select
                p.codpessoa, p.fisica, p.fantasia, p.pessoa, p.cnpj, p.ie,
                p.endereco, p.numero, p.complemento, p.bairro,
                p.codgrupoeconomico, ge.grupoeconomico,
                c.cidade, e.sigla as uf,
                p.vendedor, p.inativo
            from tblpessoa p
            left join tblgrupoeconomico ge on (ge.codgrupoeconomico = p.codgrupoeconomico)
            left join tblcidade c on (c.codcidade = p.codcidade)
            left join tblestado e on (e.codestado = c.codestado)
            where p.codpessoa = :id
            limit 1
        ';
        $rows = DB::select($sql, ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}

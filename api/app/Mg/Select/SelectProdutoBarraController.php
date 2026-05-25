<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectProdutoBarraController extends Controller
{
    public static function index(Request $request)
    {
        $busca = $request->busca ?? '';
        $page = $request->page ?? 1;
        $limite = $request->limit ?? 100;
        $offset = ($page - 1) * $limite;

        $sql = "SELECT
                    similarity(unaccent(descricao || ' ' || barras), unaccent(:frase)) AS score,
                    codprodutobarra,
                    codproduto,
                    barras,
                    descricao,
                    sigla,
                    preco,
                    marca,
                    referencia,
                    inativo,
                    'https://sistema.mgpapelaria.com.br/MGLara/public/imagens/' || imagem as imagem
                  FROM vwProdutoBarra
                 WHERE codProdutoBarra is not null ";

        if (!empty($request->codprodutobarra)) {
            return DB::select(
                $sql . ' AND codprodutobarra = :codprodutobarra LIMIT 1',
                ['frase' => '', 'codprodutobarra' => $request->codprodutobarra]
            );
        }

        if (!filter_var($request->inativo, FILTER_VALIDATE_BOOLEAN) || is_null($request->inativo)) {
            $sql .= 'AND Inativo is null ';
        }

        $ordem = null;
        $frase = '';
        $filtro = [];

        foreach (explode(' ', $busca) as $i => $palavra) {
            if ($palavra == '$') {
                $ordem = 'preco ASC, descricao ASC';
                continue;
            }
            if (preg_match('/^\d{6}$/', $palavra) === 1) {
                $sql .= " AND codproduto = :palavra_{$i}::bigint ";
                $filtro["palavra_{$i}"] = $palavra;
                if (is_null($ordem)) {
                    $ordem = 'descricao ASC, preco ASC';
                }
                continue;
            }
            if (preg_match('/^\d+(\.\d{3})*,\d{2}$/', $palavra) === 1) {
                $sql .= " AND preco = :palavra_{$i}::numeric ";
                $preco = str_replace(['.', ','], ['', '.'], $palavra);
                $filtro["palavra_{$i}"] = $preco;
                if (is_null($ordem)) {
                    $ordem = 'descricao ASC, preco ASC';
                }
                continue;
            }
            $frase .= " {$palavra}";
        }

        $filtro['frase'] = trim($frase);
        if (!empty($filtro['frase'])) {
            $sql .= " AND similarity(unaccent(descricao || ' ' || barras), unaccent(:frase)) > 0.2 ";
        }

        if (is_null($ordem)) {
            $ordem = 'score DESC, descricao ASC, preco ASC';
        }

        $sql .= " ORDER BY $ordem LIMIT $limite OFFSET $offset";
        return DB::select($sql, $filtro);
    }
}

<?php

namespace Mg\Select;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;

use Mg\Produto\ProdutoBarra;

class SelectProdutoBarraController extends Controller
{
    public static function index(Request $request)
    {

        $busca = $request->busca ?? '';
        $page = $request->page ?? 1;
        $limite = $request->limit ?? 100;
        $offset = ($page - 1) * $limite;

        // monta sql base
        $sql = "SELECT 
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

        // inativos
        if (!filter_var($request->inativo, FILTER_VALIDATE_BOOLEAN)) {
            $sql .= "AND Inativo is not null ";
        }

        // ordem padrao
        $ordem = 'descricao ASC, preco ASC';

        // percorre as palavras
        $palavras = explode(' ', $request->busca);
        $filtro = [];
        foreach ($palavras as $i => $busca) {

            if ($busca == '$') {
                $ordem = 'preco ASC, descricao ASC';
                continue;
            }

            // decide se busca por text ou por codigo
            $buscaPor = 'Texto';
            if (preg_match('/^(?:\d{1,3}(?:\.\d{3})*|\d+),\d{2}$/', $busca) === 1) {
                $buscaPor = 'Preco';
            } else if (preg_match('/^\d{6}$/', $busca) === 1) {
                $buscaPor = 'Codigo';
            }

            // monta o sql da busca
            switch ($buscaPor) {
                case 'Codigo':
                    $sql .= "
                        AND codproduto = :filtro_{$i}
                    ";
                    $filtro["filtro_{$i}"] = $busca;
                    break;
                
                case 'Preco':
                    $sql .= "
                        AND preco = :filtro_{$i}
                    ";
                    $preco = str_replace('.', '', $busca);
                    $preco = str_replace(',', '.', $busca);
                    $filtro["filtro_{$i}"] = $preco;
                    break;
                
                default:
                    $sql .= "
                        AND ( 
                            barras ilike :filtro_{$i}
                            OR descricao ilike :filtro_{$i}
                        )
                    ";
                    $filtro["filtro_{$i}"] = "%{$busca}%";
                    break;
            }

        }

        //ordena
        $sql .= " ORDER BY $ordem LIMIT $limite OFFSET $offset";

        $resultados = DB::select($sql, $filtro);
        return $resultados;
    }
}

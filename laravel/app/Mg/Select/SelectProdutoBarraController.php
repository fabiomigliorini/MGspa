<?php

namespace Mg\Select;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

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

        // inativos
        if (!filter_var($request->inativo, FILTER_VALIDATE_BOOLEAN) || is_null($request->inativo)) {
            $sql .= "AND Inativo is null ";
        }

        // ordem padrao
        $ordem = null;

        // inicializa filtro
        $frase = '';
        $filtro = [];

        // percorre as palavras
        $palavras = explode(' ', $busca);
        foreach ($palavras as $i => $palavra) {

            // se tem cifrão ordena pelo preço
            if ($palavra == '$') {
                $ordem = 'preco ASC, descricao ASC';
                continue;
            }

            // se é seis dígitos filtra pelo código do produto
            if (preg_match('/^\d{6}$/', $palavra) === 1) {
                $sql .= "
                    AND codproduto = :palavra_{$i}::bigint
                ";
                $filtro["palavra_{$i}"] = $palavra;
                if (is_null($ordem)) {
                    $ordem = 'descricao ASC, preco ASC';
                }
                continue;
            }

            // se é um valor (ex: 1.234,56) filtra pelo preço
            if (preg_match('/^\d+(\.\d{3})*,\d{2}$/', $palavra) === 1) {
                $sql .= "
                    AND preco = :palavra_{$i}::numeric
                ";
                $preco = str_replace('.', '', $palavra);
                $preco = str_replace(',', '.', $preco);
                $filtro["palavra_{$i}"] = $preco;
                if (is_null($ordem)) {
                    $ordem = 'descricao ASC, preco ASC';
                }
                continue;
            }

            // acumula a palavra para busca textual
            $frase .= " {$palavra}";
        }

        // se tem frase monta o filtro textual
        $filtro["frase"] = trim($frase);
        if (!empty($filtro["frase"])) {
            $sql .= "
                AND similarity(unaccent(descricao || ' ' || barras), unaccent(:frase)) > 0.2
            ";
        }

        if (is_null($ordem)) {
            $ordem = 'score DESC, descricao ASC, preco ASC';
        }

        // ordena
        $sql .= " ORDER BY $ordem LIMIT $limite OFFSET $offset";

        // dd($sql, $filtro);
        $resultados = DB::select($sql, $filtro);
        return $resultados;
    }
}

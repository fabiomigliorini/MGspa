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
        $page = (int) $request->page > 0 ? (int) $request->page : 1;
        $offset = ($page - 1) * 20;

        $sql = "SELECT
                    strict_word_similarity(unaccent(:frase), unaccent(descricao || ' ' || barras)) AS score,
                    codprodutobarra,
                    codproduto,
                    barras,
                    descricao,
                    sigla,
                    preco,
                    marca,
                    referencia,
                    inativo,
                    '" . config('services.mglara.imagens_url') . "/' || imagem as imagem,
                    codprodutobarra as value,
                    descricao as label
                  FROM vwProdutoBarra
                 WHERE codProdutoBarra is not null ";

        $inativos = filter_var($request->input('inativos', false), FILTER_VALIDATE_BOOLEAN);
        if ($request->has('inativo')) {
            $inativos = filter_var($request->inativo, FILTER_VALIDATE_BOOLEAN);
        }
        if (!$inativos) {
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
            $sql .= " AND strict_word_similarity(unaccent(:frase), unaccent(descricao || ' ' || barras)) > 0.4 ";
        }

        if (is_null($ordem)) {
            $ordem = 'score DESC, descricao ASC, preco ASC';
        }

        // Ordem escolhida na tela (whitelist -- nunca concatenar o que vem
        // do request).
        $ordens = [
            'alfabetica' => 'descricao ASC, preco ASC',
            'preco' => 'preco ASC, descricao ASC',
            'codigo' => 'codproduto ASC, descricao ASC',
            'barras' => 'barras ASC, descricao ASC',
        ];
        $pedida = $ordens[strtolower((string) $request->input('ordem'))] ?? null;

        if ($pedida === null) {
            $sql .= " ORDER BY $ordem LIMIT 20 OFFSET $offset";
            return DB::select($sql, $filtro);
        }

        // Com ordem escolhida, a relevancia continua sendo o filtro: ordena
        // os 200 mais relevantes, nao a base inteira. O corte de
        // similaridade e frouxo de proposito (0.4) -- buscar "caneca" casa
        // com 4.256 produtos, incluindo "Ca-60" e "Cabeca". Em ordem
        // alfabetica pura, o topo viraria "# Imobilizado Diversos". O teto
        // de 200 e o mesmo que a pesquisa do PDV usa.
        $sql = "SELECT * FROM ( {$sql} ORDER BY {$ordem} LIMIT 200 ) relevantes
                 ORDER BY {$pedida} LIMIT 20 OFFSET {$offset}";
        return DB::select($sql, $filtro);
    }

    /**
     * Resolve um codigo digitado ou bipado num produto UNICO. Duas formas,
     * as mesmas que o PDV aceita:
     *   1. o codigo de barras exato;
     *   2. codigo do produto com 6 digitos, so nas barras sem embalagem
     *      (quantidade is null).
     *
     * So responde quando a consulta devolve UM produto. Nao da para usar o
     * ProdutoService::buscaPorBarras aqui: ele faz ->first() e, num produto
     * com variacao de cor, escolheria uma cor ao acaso -- o codproduto
     * 200304 (Tinta Acrilex 37ml) tem 6 barras base, uma por cor. O PDV
     * escapa disso porque filtra ret.length == 1 no front.
     *
     * Devolve no mesmo shape do show, para a tela nao distinguir de onde veio.
     */
    public static function barras($barras)
    {
        $codigo = trim($barras);

        $sql = "SELECT
                    0 AS score,
                    codprodutobarra,
                    codproduto,
                    barras,
                    descricao,
                    sigla,
                    preco,
                    marca,
                    referencia,
                    inativo,
                    '" . config('services.mglara.imagens_url') . "/' || imagem as imagem,
                    codprodutobarra as value,
                    descricao as label
                  FROM vwProdutoBarra
                 WHERE barras = :codigo
                    OR (
                         :codigo ~ '^[0-9]{6}$'
                         AND codproduto = nullif(regexp_replace(:codigo, '\\D', '', 'g'), '')::bigint
                         AND quantidade IS NULL
                       )
                 LIMIT 10";
        $rows = DB::select($sql, ['codigo' => $codigo]);

        if (count($rows) === 0) {
            abort(404, "Nenhum produto com o código {$codigo}!");
        }
        if (count($rows) > 1) {
            abort(409, "O código {$codigo} cai em " . count($rows) . ' produtos. Use a pesquisa.');
        }
        return $rows[0];
    }

    public static function show($id)
    {
        $sql = "SELECT
                    similarity(unaccent(descricao || ' ' || barras), unaccent('')) AS score,
                    codprodutobarra,
                    codproduto,
                    barras,
                    descricao,
                    sigla,
                    preco,
                    marca,
                    referencia,
                    inativo,
                    '" . config('services.mglara.imagens_url') . "/' || imagem as imagem,
                    codprodutobarra as value,
                    descricao as label
                  FROM vwProdutoBarra
                 WHERE codProdutoBarra = :id
                 LIMIT 1";
        $rows = DB::select($sql, ['id' => $id]);
        if (empty($rows)) {
            abort(404);
        }
        return $rows[0];
    }
}

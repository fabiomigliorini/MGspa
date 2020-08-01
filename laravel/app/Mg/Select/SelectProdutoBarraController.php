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
        $busca = $request->busca??'';
        $page = $request->page??1;
        $limite = $request->limit??50;
        $offset = ($page-1)*$limite;
        // return $offset;

        // decide ordem
        $ordem = (strstr($busca, '$'))?'preco ASC, descricao ASC':'descricao ASC, preco ASC';
        $busca = str_replace('$', '', $busca);

        $sql = "SELECT codprodutobarra, codproduto, barras, descricao, sigla, preco, marca, referencia, inativo
                  FROM vwProdutoBarra
                 WHERE codProdutoBarra is not null ";

        if (!filter_var($request->inativo, FILTER_VALIDATE_BOOLEAN)) {
            $sql .= "AND Inativo is not null ";
        }

        $palavras = explode(' ', $request->busca);
        $where = 'where';
        $filtro = [];
        foreach ($palavras as $i => $busca) {
            $sql .= 'AND (';
            // Verifica se foi digitado um valor e procura pelo preco
            if ((numeroLimpo($busca) == $busca)
                    && (strpos($busca, ",") != 0)
                    && ((strlen($busca) - strpos($busca, ",")) == 3)) {
                $sql .= "preco = :filtro{$i}";
                $filtro["filtro{$i}"] = numeroLimpo($busca);
            }
            //senao procura por barras, descricao, marca e referencia
            else {
                $sql .= "barras ilike :filtro{$i} ";
                $sql .= "OR descricao ilike :filtro{$i} ";
                $sql .= "OR referencia ilike :filtro{$i} ";
                $filtro["filtro{$i}"] = "%{$busca}%";
            }

            $sql .= ')';
        }

        //ordena
        $sql .= " ORDER BY $ordem LIMIT $limite OFFSET $offset";

        return $sql;

        $resultados = DB::select($sql, $filtro);
        // return $resultados;
        // for ($i=0; $i<sizeof($resultados);$i++) {
        //     $resultados[$i]["codproduto"] = Yii::app()->format->formataCodigo($resultados[$i]["codproduto"], 6);
        //     $resultados[$i]["preco"] = Yii::app()->format->formatNumber($resultados[$i]["preco"]);
        //     if (empty($resultados[$i]["referencia"])) {
        //         $resultados[$i]["referencia"] = "-";
        //     }
        // }

        // transforma o array em JSON
        return $resultados;

        $sql = "
            with prod as (
            	select
            		p.codproduto,
            		pv.codprodutovariacao,
            		pb.codprodutobarra,
            		pb.barras,
            		p.produto || coalesce(' ' || pv.variacao, '') || coalesce(' ' || pb.variacao, '') || coalesce(' C/' || cast(pe.quantidade as bigint), '') as produto,
            		coalesce(p.referencia, '') || coalesce(pv.referencia, '')  || coalesce(pb.referencia, '') as referencia,
            		coalesce(pe.preco, p.preco * coalesce(pe.quantidade, 1)) as preco,
            		coalesce(pv.inativo, p.inativo) as inativo,
            		pv.descontinuado
            	from tblproduto p
            	inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
            	inner join tblprodutobarra pb on (pb.codprodutovariacao  = pv.codprodutovariacao and pb.codproduto = p.codproduto)
            	left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
            )
            select *
            from prod p
        ";

        $palavras = explode(' ', $request->busca);
        $where = 'where';
        $filtro = [];
        foreach ($palavras as $i => $palavra) {
            $sql .= "
                $where p.produto || ' ' || p.referencia ilike :filtro{$i}
            ";
            $filtro["filtro{$i}"] = "%{$palavra}%";
            $where = 'and';
        }

        if ($request->ativo) {
            $sql .= "
                $where p.inativo is null
            ";
            $where = 'and';
        }

        if ($request->inativo) {
            $sql .= "
                $where p.inativo is not null
            ";
            $where = 'and';
        }

        $sql .= '
            order by p.produto, p.preco, p.referencia, p.barras
            limit 50
        ';

        $prods = DB::select($sql, $filtro);
        return $prods;

        return $filtro;
        dd($sql);

        // busca filiais
        $qry = Filial::ativo()->select([
            'codfilial',
            'filial'
        ])->orderBy('codempresa')->orderBy('codfilial');
        if (filter_var($request->dfe, FILTER_VALIDATE_BOOLEAN)) {
            $qry->where('dfe', true);
        }
        $ret = $qry->get();

        // renomeia colunas
        $ret = $ret->map(function ($item) {
            return [
                'value' => $item->codfilial,
                'label' => $item->filial,
            ];
        });

        // retorna
        return $ret;
    }
}

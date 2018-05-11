<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use Carbon\Carbon;
use DB;

use App\Models\EstoqueLocalProdutoVariacaoVenda;

class EstoqueLocalProdutoVariacaoVendaRepository extends MGRepositoryStatic
{
    public static $modelClass = 'App\\Models\\EstoqueLocalProdutoVariacaoVenda';

    public static function validationRules ($model = null)
    {
        $rules = [
            'codestoquelocalprodutovariacao' => [
                'numeric',
                'required',
            ],
            'mes' => [
                'date',
                'required',
            ],
            'quantidade' => [
                'numeric',
                'nullable',
            ],
            'valor' => [
                'numeric',
                'nullable',
            ],
        ];

        return $rules;
    }

    public static function validationMessages ($model = null)
    {
        $messages = [
            'codestoquelocalprodutovariacao.numeric' => 'O campo "codestoquelocalprodutovariacao" deve ser um número!',
            'codestoquelocalprodutovariacao.required' => 'O campo "codestoquelocalprodutovariacao" deve ser preenchido!',
            'mes.date' => 'O campo "mes" deve ser uma data!',
            'mes.required' => 'O campo "mes" deve ser preenchido!',
            'quantidade.numeric' => 'O campo "quantidade" deve ser um número!',
            'valor.numeric' => 'O campo "valor" deve ser um número!',
        ];

        return $messages;
    }

    public static function details($model)
    {
        return parent::details ($model);
    }

    public static function query(array $filter = null, array $sort = null, array $fields = null)
    {
        return parent::query ($filter, $sort, $fields);
    }

    public static function sumarizaPorMes ()
    {
        $execucao = Carbon::now();

        // Cria tabela temporaria com as vendas
        $sql = "
            create temporary table tmpestoquelocalprodutovariacaovenda as
            select
                tblprodutobarra.codprodutovariacao
                , tblnegocio.codestoquelocal
                , date_trunc('month', tblnegocio.lancamento) as mes
                , sum(tblnegocioprodutobarra.quantidade * coalesce(tblprodutoembalagem.quantidade, 1) * (case when tblnaturezaoperacao.codoperacao = 1 then -1 else 1 end)) as quantidade
                , sum(tblnegocioprodutobarra.valortotal * (case when tblnaturezaoperacao.codoperacao = 1 then -1 else 1 end)) as valor
                , cast(null as bigint) as codestoquelocalprodutovariacao
            from tblnegocio
            inner join tblnaturezaoperacao on (tblnaturezaoperacao.codnaturezaoperacao = tblnegocio.codnaturezaoperacao)
            inner join tblnegocioprodutobarra on (tblnegocioprodutobarra.codnegocio = tblnegocio.codnegocio)
            inner join tblprodutobarra on (tblprodutobarra.codprodutobarra = tblnegocioprodutobarra.codprodutobarra)
            left join tblprodutoembalagem on (tblprodutoembalagem.codprodutoembalagem = tblprodutobarra.codprodutoembalagem)
            where tblnegocio.codnegociostatus = 2 --Fechado
            and (tblnaturezaoperacao.venda = true or tblnaturezaoperacao.vendadevolucao = true)
            --and tblprodutobarra.codproduto in (select tblproduto.codproduto from tblproduto where tblproduto.codmarca = 29) -- ACRILEX
            --and tblprodutobarra.codproduto = 555
            group by
                 tblprodutobarra.codprodutovariacao
                , tblnegocio.codestoquelocal
                , date_trunc('month', tblnegocio.lancamento)
            ";

        if (!$ret = DB::statement($sql)) {
            return false;
        }

        // atualiza campo codestoquelocalprodutovariacao da tabela temporaria
        $sql_codestoquelocalprodutovariacao = "
            update tmpestoquelocalprodutovariacaovenda
            set codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao
            from tblestoquelocalprodutovariacao elpv
            where tmpestoquelocalprodutovariacaovenda.codestoquelocalprodutovariacao is null
            and tmpestoquelocalprodutovariacaovenda.codestoquelocal = elpv.codestoquelocal
            and tmpestoquelocalprodutovariacaovenda.codprodutovariacao = elpv.codprodutovariacao
        ";
        $ret = DB::update($sql_codestoquelocalprodutovariacao);

        // Cria registros de EstoqueLocalProdutoVariacao para as combinacoes que nao existirem
        $sql = "
            insert into tblestoquelocalprodutovariacao (codestoquelocal, codprodutovariacao, criacao, alteracao)
            select distinct tmp.codestoquelocal, tmp.codprodutovariacao, '{$execucao->toIso8601String()}'::timestamp, '{$execucao->toIso8601String()}'::timestamp
            from tmpestoquelocalprodutovariacaovenda tmp
            where tmp.codestoquelocalprodutovariacao is null
            ";
        $ret = DB::insert($sql);
        $ret = DB::update($sql_codestoquelocalprodutovariacao);


        // Atualiza na tabela de vendas, aquilo que ja existe
        $sql = "
            update tblestoquelocalprodutovariacaovenda
            set quantidade = tmp.quantidade
            , valor = tmp.valor
            , alteracao = '{$execucao->toIso8601String()}'::timestamp
            from tmpestoquelocalprodutovariacaovenda tmp
            where tblestoquelocalprodutovariacaovenda.codestoquelocalprodutovariacao = tmp.codestoquelocalprodutovariacao
            and tblestoquelocalprodutovariacaovenda.mes = tmp.mes
            ";
        $ret = DB::update($sql);

        // Cria na tabela de vendas novos registros de venda
        $sql = "
            insert into tblestoquelocalprodutovariacaovenda (codestoquelocalprodutovariacao, mes, quantidade, valor, criacao, alteracao)
            select tmp.codestoquelocalprodutovariacao, tmp.mes, tmp.quantidade, tmp.valor, '{$execucao->toIso8601String()}'::timestamp, '{$execucao->toIso8601String()}'::timestamp
            from tmpestoquelocalprodutovariacaovenda tmp
            left join tblestoquelocalprodutovariacaovenda venda on (venda.codestoquelocalprodutovariacao = tmp.codestoquelocalprodutovariacao and venda.mes = tmp.mes)
            where venda.codestoquelocalprodutovariacaovenda is null
            ";

        $ret = DB::insert($sql);

        // Apaga registros de venda obsoletos
        $sql = "
            delete from tblestoquelocalprodutovariacaovenda
            where alteracao < '{$execucao->toIso8601String()}'::timestamp
            ";

        $ret = DB::delete($sql);

        // elimina tabela temporaria
        $sql = "
            drop table tmpestoquelocalprodutovariacaovenda
            ";

        if (!$ret = DB::statement($sql)) {
            return false;
        }

        return true;

    }


}

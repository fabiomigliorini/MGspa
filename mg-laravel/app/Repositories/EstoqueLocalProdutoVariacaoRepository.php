<?php

namespace App\Repositories;

use DB;
use Carbon\Carbon;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\EstoqueLocalProdutoVariacao;

class EstoqueLocalProdutoVariacaoRepository extends MGRepositoryStatic
{
    public static $modelClass = 'App\\Models\\EstoqueLocalProdutoVariacao';

    public static function validationRules ($model = null)
    {
        $rules = [
            'codestoquelocal' => [
                'numeric',
                'required',
            ],
            'corredor' => [
                'numeric',
                'nullable',
            ],
            'prateleira' => [
                'numeric',
                'nullable',
            ],
            'coluna' => [
                'numeric',
                'nullable',
            ],
            'bloco' => [
                'numeric',
                'nullable',
            ],
            'estoqueminimo' => [
                'numeric',
                'nullable',
            ],
            'estoquemaximo' => [
                'numeric',
                'nullable',
            ],
            'codprodutovariacao' => [
                'numeric',
                'required',
            ],
            'vendabimestrequantidade' => [
                'numeric',
                'nullable',
            ],
            'vendabimestrevalor' => [
                'numeric',
                'nullable',
            ],
            'vendasemestrequantidade' => [
                'numeric',
                'nullable',
            ],
            'vendasemestrevalor' => [
                'numeric',
                'nullable',
            ],
            'vendaanoquantidade' => [
                'numeric',
                'nullable',
            ],
            'vendaanovalor' => [
                'numeric',
                'nullable',
            ],
            'vendaultimocalculo' => [
                'date',
                'nullable',
            ],
            'vencimento' => [
                'date',
                'nullable',
            ],
            'vendadiaquantidadeprevisao' => [
                'numeric',
                'nullable',
            ],
        ];

        return $rules;
    }

    public static function validationMessages ($model = null)
    {
        $messages = [
            'codestoquelocal.numeric' => 'O campo "codestoquelocal" deve ser um número!',
            'codestoquelocal.required' => 'O campo "codestoquelocal" deve ser preenchido!',
            'corredor.numeric' => 'O campo "corredor" deve ser um número!',
            'prateleira.numeric' => 'O campo "prateleira" deve ser um número!',
            'coluna.numeric' => 'O campo "coluna" deve ser um número!',
            'bloco.numeric' => 'O campo "bloco" deve ser um número!',
            'estoqueminimo.numeric' => 'O campo "estoqueminimo" deve ser um número!',
            'estoquemaximo.numeric' => 'O campo "estoquemaximo" deve ser um número!',
            'codprodutovariacao.numeric' => 'O campo "codprodutovariacao" deve ser um número!',
            'codprodutovariacao.required' => 'O campo "codprodutovariacao" deve ser preenchido!',
            'vendabimestrequantidade.numeric' => 'O campo "vendabimestrequantidade" deve ser um número!',
            'vendabimestrevalor.numeric' => 'O campo "vendabimestrevalor" deve ser um número!',
            'vendasemestrequantidade.numeric' => 'O campo "vendasemestrequantidade" deve ser um número!',
            'vendasemestrevalor.numeric' => 'O campo "vendasemestrevalor" deve ser um número!',
            'vendaanoquantidade.numeric' => 'O campo "vendaanoquantidade" deve ser um número!',
            'vendaanovalor.numeric' => 'O campo "vendaanovalor" deve ser um número!',
            'vendaultimocalculo.date' => 'O campo "vendaultimocalculo" deve ser uma data!',
            'vencimento.date' => 'O campo "vencimento" deve ser uma data!',
            'vendadiaquantidadeprevisao.numeric' => 'O campo "vendadiaquantidadeprevisao" deve ser um número!',
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

    public static function buscaOuCria($codestoquelocal, $codprodutovariacao)
    {
        if ($model = EstoqueLocalProdutoVariacao::where('codestoquelocal', $codestoquelocal)->where('codprodutovariacao', $codprodutovariacao)->first()) {
            return $model;
        }
        $model = static::new([
            'codestoquelocal' => $codestoquelocal,
            'codprodutovariacao' => $codprodutovariacao,
        ]);
        return static::save($model);
    }

    public static function calculaVenda ($codestoquelocal, $codprodutovariacao)
    {

        // Busca todos produtos variacao
        $pvs = \App\Models\ProdutoVariacao::orderBy('codprodutovariacao');
        if (!empty($codprodutovariacao)) {
            $pvs->where('codprodutovariacao', $codprodutovariacao);
        }

        // Percorre ajustando a data da ultima compra
        foreach ($pvs->get() as $pv) {
            $data = null;
            $quantidade = null;
            $custo = null;
            $sql = "
                select
                    nf.emissao
                    , sum(nfpb.quantidade * coalesce(pe.quantidade, 1)) as quantidade
                    , sum(nfpb.valortotal) as valortotal
                from tblprodutobarra pb
                left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
                inner join tblnotafiscalprodutobarra nfpb on (nfpb.codprodutobarra = pb.codprodutobarra)
                inner join tblnotafiscal nf on (nf.codnotafiscal = nfpb.codnotafiscal)
                inner join tblnaturezaoperacao no on (no.codnaturezaoperacao = nf.codnaturezaoperacao)
                where pb.codprodutovariacao = {$pv->codprodutovariacao}
                and no.compra = true
                group by pb.codprodutovariacao, nf.emissao
                order by nf.emissao desc
                limit 1
                ";
            $compra = DB::select($sql);
            if (isset($compra[0])) {
                $data = $compra[0]->emissao;
                $quantidade = $compra[0]->quantidade;
                $custo = $compra[0]->valortotal;
            }
            if ($quantidade > 0) {
                $custo /= $quantidade;
            }
            $ret = \App\Models\ProdutoVariacao::where('codprodutovariacao', $pv->codprodutovariacao)->update([
                'dataultimacompra' => $data,
                'quantidadeultimacompra' => $quantidade,
                'custoultimacompra' => $custo,
            ]);
        }

        // Monta faixa de datas
        $bimestre = new Carbon('today - 2 months');
        $semestre = new Carbon('today - 6 months');
        $ano = new Carbon('today - 1 year');
        $agora = new Carbon('now');

        // consulta total vendido
        $sql = "
            select
                tblnegocio.codestoquelocal
                , tblprodutobarra.codprodutovariacao
                --, tblprodutobarra.codproduto
                , sum(tblnegocioprodutobarra.quantidade * coalesce(tblprodutoembalagem.quantidade, 1)) as vendaanoquantidade
                , sum(tblnegocioprodutobarra.valortotal) as vendaanovalor
                , sum(case when (tblnegocio.lancamento >= '{$semestre->toIso8601String()}') then tblnegocioprodutobarra.quantidade * coalesce(tblprodutoembalagem.quantidade, 1) else 0 end) as vendasemestrequantidade
                , sum(case when (tblnegocio.lancamento >= '{$semestre->toIso8601String()}') then tblnegocioprodutobarra.valortotal * (tblnegocio.valortotal / tblnegocio.valorprodutos) else 0 end) as vendasemestrevalor
                , sum(case when (tblnegocio.lancamento >= '{$bimestre->toIso8601String()}') then tblnegocioprodutobarra.quantidade * coalesce(tblprodutoembalagem.quantidade, 1) else 0 end) as vendabimestrequantidade
                , sum(case when (tblnegocio.lancamento >= '{$bimestre->toIso8601String()}') then tblnegocioprodutobarra.valortotal * (tblnegocio.valortotal / tblnegocio.valorprodutos) else 0 end) as vendabimestrevalor
            from tblnegocio
            inner join tblnaturezaoperacao on (tblnaturezaoperacao.codnaturezaoperacao = tblnegocio.codnaturezaoperacao)
            inner join tblnegocioprodutobarra on (tblnegocioprodutobarra.codnegocio = tblnegocio.codnegocio)
            inner join tblprodutobarra on (tblprodutobarra.codprodutobarra = tblnegocioprodutobarra.codprodutobarra)
            left join tblprodutoembalagem on (tblprodutoembalagem.codprodutoembalagem = tblprodutobarra.codprodutoembalagem)
            where tblnegocio.codnegociostatus = 2 --Fechado
            and tblnegocio.lancamento >= '{$ano->toIso8601String()}'
            and tblnaturezaoperacao.venda = true
            --and tblprodutobarra.codproduto in (select tblproduto.codproduto from tblproduto where tblproduto.codmarca = 29) -- ACRILEX
            ";
        if (!empty($codprodutovariacao)) {
            $sql .= "
                and tblprodutobarra.codprodutovariacao = {$codprodutovariacao}
            ";
        }
        if (!empty($codestoquelocal)) {
            $sql .= "
                and tblnegocio.codestoquelocal = {$codestoquelocal}
            ";
        }
        $sql .= "
            group by
                    tblnegocio.codestoquelocal
                    , tblprodutobarra.codprodutovariacao
                    , tblprodutobarra.codproduto
        ";

        // Atualiza total vendido
        $regs = DB::select($sql);
        $atualizados = [];
        $diassemestre = $semestre->diffInDays();
        foreach ($regs as $reg) {
            $elpv = static::buscaOuCria($reg->codestoquelocal, $reg->codprodutovariacao);
            $elpv->vendaanoquantidade = $reg->vendaanoquantidade;
            $elpv->vendaanovalor = $reg->vendaanovalor;
            $elpv->vendasemestrequantidade = $reg->vendasemestrequantidade;
            $elpv->vendasemestrevalor = $reg->vendasemestrevalor;
            $elpv->vendabimestrequantidade = $reg->vendabimestrequantidade;
            $elpv->vendabimestrevalor = $reg->vendabimestrevalor;
            $elpv->vendaultimocalculo = $agora;
            $elpv->vendadiaquantidadeprevisao = ($reg->vendasemestrequantidade / $diassemestre);
            static::save($elpv);
            $atualizados[] = $elpv->codestoquelocalprodutovariacao;
        }

        // Limpa total vendido dos que nao foram atualizados
        $elpvs = EstoqueLocalProdutoVariacao::whereNotIn('codestoquelocalprodutovariacao', $atualizados);
        if (!empty($codprodutovariacao)) {
            $elpvs = $elpvs->where('codprodutovariacao', $codprodutovariacao);
        }
        if (!empty($codestoquelocal)) {
            $elpvs = $elpvs->where('codestoquelocal', $codestoquelocal);
        }
        $ret = $elpvs->update([
            'vendaanoquantidade' => null,
            'vendaanovalor' => null,
            'vendasemestrequantidade' => null,
            'vendasemestrevalor' => null,
            'vendabimestrequantidade' => null,
            'vendabimestrevalor' => null,
            'vendaultimocalculo' => $agora,
            'vendadiaquantidadeprevisao' => null,
        ]);

        // Limpa Estoque Minimo e Estoque Maximo
        $sql = "
            update tblestoquelocalprodutovariacao
            set estoqueminimo = null
            , estoquemaximo = null
            where estoqueminimo is not null
            or estoquemaximo is not null
        ";
        $afetados = DB::update($sql);

        // Calcula Estoque Minimo/Maximo das Lojas
        $sql = "
            update tblestoquelocalprodutovariacao
            set estoqueminimo = ceil(vendadiaquantidadeprevisao * m.estoqueminimodias)
            , estoquemaximo = ceil(vendadiaquantidadeprevisao * m.estoquemaximodias)
            from tblprodutovariacao pv
            inner join tblproduto p on (p.codproduto = pv.codproduto)
            inner join tblmarca m on (m.codmarca = coalesce(pv.codmarca, p.codmarca))
            where tblestoquelocalprodutovariacao.codprodutovariacao = pv.codprodutovariacao
            and codestoquelocal != 101001
        ";
        $afetados = DB::update($sql);

        // Calcula Estoque Minimo/Maximo do Deposito pela venda das lojas
        $sql = "
            update tblestoquelocalprodutovariacao
            set estoqueminimo = ceil(iq.vendadiaquantidadeprevisao * 15) -- 15 dias
            , estoquemaximo = ceil(iq.vendadiaquantidadeprevisao * 60) -- 60 dias
            from (
            	select elpv_iq.codprodutovariacao, sum(coalesce(elpv_iq.vendadiaquantidadeprevisao, 0)) as vendadiaquantidadeprevisao
            	from tblestoquelocalprodutovariacao elpv_iq
            	where elpv_iq.codestoquelocal != 101001 -- deposito
            	group by elpv_iq.codprodutovariacao
            	) iq
            where tblestoquelocalprodutovariacao.codprodutovariacao = iq.codprodutovariacao
            and tblestoquelocalprodutovariacao.codestoquelocal = 101001
        ";
        $afetados = DB::update($sql);

        // Coloca estoque maximo como dobro do minimo quando maximo igual a minimo
        $sql = "
            update tblestoquelocalprodutovariacao
            set estoquemaximo = estoqueminimo + 1
            where estoquemaximo <= estoqueminimo
        ";
        $afetados = DB::update($sql);

    }
}

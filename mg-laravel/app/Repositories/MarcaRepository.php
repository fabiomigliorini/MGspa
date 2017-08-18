<?php

namespace App\Repositories;

use DB;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\Marca;

/**
 * Description of MarcaRepository
 *
 */
class MarcaRepository extends MGRepositoryStatic
{
    public static $modelClass = '\\App\\Models\\Marca';

    public static function validationRules ($model = null)
    {
        $rules = [
            'marca' => [
                'required',
                Rule::unique('tblmarca')->ignore($model->codmarca, 'codmarca'),
                'min:2'
            ],
        ];

        return $rules;
    }

    public static function validationMessages($model = null)
    {
        $messages = [
            'marca.required' => 'O campo marca não pode ser vazio',
            'marca.unique' => 'Esta marca já esta cadastrada',
            'narca.min' => 'O campo Marca deve ter no mínimo 2 caracteres.',
        ];

        return $messages;
    }

    public static function query(array $filter = null, array $sort = null, array $fields = null)
    {
        $qry = app(static::$modelClass)::query();

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        if (!empty($filter['marca'])) {
            $qry->palavras('marca', $filter['marca']);
        }

        if (filter_var($filter['sobrando']??false, FILTER_VALIDATE_BOOLEAN)) {
            $qry->where('itensacimamaximo', '>', 0);
        }

        if (filter_var($filter['faltando']??false, FILTER_VALIDATE_BOOLEAN)) {
            $qry->where('itensabaixominimo', '>', 0);
        }

        if (!empty($filter['abccategoria']['min'])) {
            $qry->where('abccategoria', '>=', $filter['abccategoria']['min']);
        }

        if (!empty($filter['abccategoria']['max'])) {
            $qry->where('abccategoria', '<=', $filter['abccategoria']['max']);
        }

        $qry = static::querySort($qry, $sort);
        $qry = static::queryFields($qry, $fields);
        return $qry;
    }


    public static function calculaVenda()
    {
        // Limpa dados do ultimo calculo
        Marca::query()->update([
            'dataultimacompra' => null,
            'itensabaixominimo' => null,
            'itensacimamaximo' => null,
            'vendabimestrevalor' => null,
            'vendasemestrevalor' => null,
            'vendaanovalor' => null,
            'vendaanopercentual' => null,
        ]);

        // Calcula total de vendas baseado no calculo dos produtos
        $sql = "
            update tblmarca
            set vendabimestrevalor = iq.vendabimestrevalor
            , vendasemestrevalor = iq.vendasemestrevalor
            , vendaanovalor = iq.vendaanovalor
            from (
            	select
                    coalesce(pv.codmarca, p.codmarca) as codmarca
                    , sum(vendabimestrevalor) as vendabimestrevalor
                    , sum(vendasemestrevalor) as vendasemestrevalor
                    , sum(vendaanovalor) as vendaanovalor
            	from tblestoquelocalprodutovariacao elpv
            	inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao)
            	inner join tblproduto p on (p.codproduto = pv.codproduto)
            	group by coalesce(pv.codmarca, p.codmarca)
            	order by vendaanovalor desc nulls last
            ) iq
            where (tblmarca.codmarca = iq.codmarca)
        ";
        $afetados = DB::update($sql);

        // Monta classificacao ABC
        $totalvendaano_geral = Marca::sum('vendaanovalor');
        $totalvendaano = Marca::where('abcignorar', '=', false)->sum('vendaanovalor');
        $posicao = 0;
        $percentual_acumulado = 0;

        foreach (Marca::orderByRaw('vendaanovalor DESC NULLS LAST')->orderBy('marca', 'ASC')->get() as $marca) {
            $abccategoria = 0;
            $abcposicao = null;

            if (!$marca->abcignorar) {
                $posicao++;
                $abcposicao = $posicao;
                $percentual_acumulado += (($marca->vendaanovalor / $totalvendaano) * 100);
                if ($percentual_acumulado <= 20) {
                    $abccategoria = 3;
                } elseif ($percentual_acumulado <= 50) {
                    $abccategoria = 2;
                } elseif ($percentual_acumulado <= 90) {
                    $abccategoria = 1;
                } else {
                    $abccategoria = 0;
                }
            }

            $marca->update([
                'abccategoria' => $abccategoria,
                'abcposicao' => $abcposicao,
                'vendaanopercentual' => (($marca->vendaanovalor / $totalvendaano_geral) * 100),
            ]);

            $afetados++;
        }

        // Atualiza data da ultima compra Baseado no ProdutoVariacao
        $sql = "
            update tblmarca
            set dataultimacompra = iq.dataultimacompra
            from (
            	select coalesce(pv.codmarca, p.codmarca) as codmarca, max(dataultimacompra) as dataultimacompra
            	from tblprodutovariacao pv
            	inner join tblproduto p on (p.codproduto = pv.codproduto)
            	group by coalesce(pv.codmarca, p.codmarca)
            	) iq
            where tblmarca.codmarca = iq.codmarca
        ";
        $afetados += DB::update($sql);

        // Calcula quantidade de Itens acima do maximo ou abaixo do minimo
        // baseado na soma do estoque de todos locais
        // e na soma das quantidades minimas e maximas de cada EstoqueLocalProdutoVariacao
        $sql = "
            update tblmarca
            set itensabaixominimo = iq3.itensabaixominimo
            , itensacimamaximo = iq3.itensacimamaximo
            from (
            	select
            		coalesce(pv.codmarca, p.codmarca) as codmarca,
            		sum(case when coalesce(iq2.saldoquantidade, 0) < coalesce(iq2.estoqueminimo, 0) then 1 else 0 end) as itensabaixominimo,
            		sum(case when coalesce(iq2.saldoquantidade, 0) > coalesce(iq2.estoquemaximo, 0) then 1 else 0 end) as itensacimamaximo
            	from tblprodutovariacao pv
            	inner join tblproduto p on (p.codproduto = pv.codproduto)
            	left join (
                    select elpv.codprodutovariacao
            		, sum(coalesce(elpv.estoqueminimo, 0)) as estoqueminimo
            		, sum(coalesce(elpv.estoquemaximo, 0)) as estoquemaximo
            		, sum(coalesce(es.saldoquantidade, 0)) as saldoquantidade
            		from tblestoquelocalprodutovariacao elpv
            		left join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
            		group by elpv.codprodutovariacao
            		) iq2 on (iq2.codprodutovariacao = pv.codprodutovariacao)
            	group by coalesce(pv.codmarca, p.codmarca)
            ) iq3
            where iq3.codmarca = tblmarca.codmarca
        ";
        $afetados += DB::update($sql);

        return $afetados;
    }

    public static function details ($model)
    {
        $data = $model->getAttributes();
        if (!empty($model->codimagem)) {
            $data['imagem'] = $model->Imagem->getAttributes();
            $data['imagem']['url'] = $model->Imagem->url;
        }

        $data['produtosAbaixoMinimo'] = MarcaRepository::produtosAbaixoMinimo($model);
        $data['produtosAcimaMaximo'] = MarcaRepository::produtosAcimaMaximo($model);

        return $data;
    }

    /**
     * Busca listagem de produtos da Marca Acima do Estoque Maximo
     */
    public static function produtosAcimaMaximo ($model)
    {
        return static::produtos($model, 'acimaMaximo');
    }

    /**
     * Busca listagem de produtos da Marca Abaixo do Estoque Minimo
     */
    public static function produtosAbaixoMinimo ($model)
    {
        return static::produtos($model, 'abaixoMinimo');
    }

    /**
     * Busca listagem de produtos da Marca
     */
    public static function produtos ($model, $filtro)
    {
        $sql = "
            select
                p.codproduto,
                pv.codprodutovariacao,
                p.produto,
                p.preco,
                um.sigla as unidademedida,
                pv.variacao,
                coalesce(pv.referencia, p.referencia) as referencia,
                sld.estoqueminimo,
                sld.estoquemaximo,
                sld.saldoquantidade,
                sld.vendadiaquantidadeprevisao,
                case when coalesce(sld.vendadiaquantidadeprevisao, 0) != 0 then (sld.saldoquantidade / sld.vendadiaquantidadeprevisao) else null end as dias,
                sld.saldovalor,
                pv.dataultimacompra,
                pv.custoultimacompra,
                pv.quantidadeultimacompra,
                i.arquivo as imagem
            from tblprodutovariacao pv
            inner join tblproduto p on (p.codproduto = pv.codproduto)
            inner join tblunidademedida um on (um.codunidademedida = p.codunidademedida)
            inner join (
                select elpv.codprodutovariacao
                , sum(coalesce(elpv.estoqueminimo, 0)) as estoqueminimo
                , sum(coalesce(elpv.estoquemaximo, 0)) as estoquemaximo
                , sum(coalesce(es.saldoquantidade, 0)) as saldoquantidade
                , sum(coalesce(es.saldovalor, 0)) as saldovalor
                , sum(coalesce(elpv.vendadiaquantidadeprevisao, 0)) as vendadiaquantidadeprevisao
                from tblestoquelocalprodutovariacao elpv
                inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
                group by elpv.codprodutovariacao
                ) sld on (sld.codprodutovariacao = pv.codprodutovariacao)
            left join tblprodutoimagem pi on (pi.codprodutoimagem = coalesce(pv.codprodutoimagem, p.codprodutoimagem))
            left join tblimagem i on (i.codimagem = pi.codimagem)
            where coalesce(pv.codmarca, p.codmarca) = {$model->codmarca}
            and p.inativo is null
            ";

        switch ($filtro) {
            case 'abaixoMinimo':
                $sql .= "
                    and coalesce(sld.saldoquantidade, 0) < coalesce(sld.estoqueminimo, 0)
                ";
                break;

            case 'acimaMaximo':
                $sql .= "
                    and coalesce(sld.saldoquantidade, 0) > coalesce(sld.estoquemaximo, 0)
                ";
                break;
        }
        $sql .= "
            order by p.produto, pv.variacao
        ";

        $prods = DB::select($sql);

        foreach ($prods as $i => $prod) {
            if(!empty($prod->imagem)) {
                $prods[$i]->imagem = url(asset("imagens/{$prod->imagem}"));
            }
        }

        return $prods;
    }
}

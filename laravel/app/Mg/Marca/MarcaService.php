<?php

namespace Mg\Marca;
use Mg\MgService;
use Mg\Estoque\EstoqueLocal;

class MarcaService extends MgService
{
    public static function pesquisar(array $filter = null, array $sort = null, array $fields = null)
    {
        $qry = Marca::query();

        if (!empty($filter['codmarca'])) {
            $qry->where('codmarca', $filter['codmarca']);
        }

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        if (!empty($filter['marca'])) {
            $qry->palavras('marca', $filter['marca']);
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

        $qry = self::qryOrdem($qry, $sort);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    /**
     * Busca Autocomplete do Quasar
     */
    public static function autocompletar ($params)
    {
        $qry = static::pesquisar($params)
                ->select('codmarca', 'marca')
                ->take(20);
        $ret = [];
        foreach ($qry->get() as $item) {
            $ret[] = [
                'label' => $item->marca,
                'value' => $item->marca,
                'id' => $item->codmarca,
            ];
        }
        return $ret;
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

        return $afetados;
    }

    public static function detalhes($id)
    {
        $model = Marca::findOrFail($id);
        if (!empty($model->codimagem)) {
            $model['imagem'] = $model->Imagem;
            $model['imagem']['url'] = $model->Imagem->url;
        }

        $model['produtosAbaixoMinimo'] = []; //MarcaService::produtosAbaixoMinimo($model);
        $model['produtosAcimaMaximo'] = []; //MarcaService::produtosAcimaMaximo($model);

        return $model;
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

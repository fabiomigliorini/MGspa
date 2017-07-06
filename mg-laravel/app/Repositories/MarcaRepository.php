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

    public static function validationRules($model = null)
    {
        $rules = [
            'marca' => [
                'required',
                Rule::unique('tblmarca')->ignore($data['id']??null, 'codmarca')
            ],
        ];

        return $rules;
    }

    public static function validationMessages($model = null)
    {
        $messages = [
            'marca.required' => 'O campo marca não pode ser vazio',
            'marca.unique' => 'Esta marca já esta cadastrada',
        ];

        return $messages;
    }

    public static function query(array $filter = null, array $sort = null, array $fields = null)
    {
        $qry = app(static::$modelClass)::query();

        $qry->AtivoInativo($filter['inativo']);

        if (!empty($filter['marca'])) {
            $qry->palavras('marca', $filter['marca']);
        }

        if (filter_var($filter['sobrando']??false, FILTER_VALIDATE_BOOLEAN)) {
            $qry->where('itensacimamaximo', '>', 0);
        }

        if (filter_var($filter['faltando']??false, FILTER_VALIDATE_BOOLEAN)) {
            $qry->where('itensabaixominimo', '>', 0);
        }

        if (!empty($filter['abccategoria'])) {
            $qry->where('abccategoria', $filter['abccategoria']);
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
            'vendaanovalor' => null
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
        $totalvendaano = Marca::sum('vendaanovalor');
        $posicao = 0;
        $percentual_acumulado = 0;

        foreach (Marca::orderByRaw('vendaanovalor DESC NULLS LAST')->orderBy('marca', 'ASC')->get() as $marca) {

            $posicao++;

            $percentual = (($marca->vendaanovalor / $totalvendaano) * 100);
            $percentual_acumulado += $percentual;
            if ($percentual_acumulado <= 20) {
                $categoria = 1;
            } elseif ($percentual_acumulado <= 50) {
                $categoria = 2;
            } elseif ($percentual_acumulado <= 90) {
                $categoria = 3;
            } else {
                $categoria = 4;
            }

            $marca->update([
                'abccategoria' => $categoria,
                'abcposicao' => $posicao,
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
            		, sum(coalesce(iq.saldoquantidade, 0)) as saldoquantidade
            		from tblestoquelocalprodutovariacao elpv
            		left join (
            			select sum(coalesce(es.saldoquantidade, 0)) as saldoquantidade, es.codestoquelocalprodutovariacao
            			from tblestoquesaldo es
            			where es.fiscal = false
            			group by es.codestoquelocalprodutovariacao
            			) iq on (iq.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
            		group by elpv.codprodutovariacao
            		) iq2 on (iq2.codprodutovariacao = pv.codprodutovariacao)
            	group by coalesce(pv.codmarca, p.codmarca)
            ) iq3
            where iq3.codmarca = tblmarca.codmarca
        ";
        $afetados += DB::update($sql);

        return $afetados;
    }
}

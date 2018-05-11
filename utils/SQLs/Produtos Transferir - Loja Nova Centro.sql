/*
select 
	-- m.marca, 
	p.codproduto, 
	-- p.produto, 
	-- pv.variacao, 
	p.produto || coalesce(' | ' || pv.variacao, ''),
	um.sigla,
	-- p.preco,
	--coalesce(pv.referencia, p.referencia), 
	(
		select pb.barras || coalesce(' ' || pe_um.sigla || ' C/' || cast(pe.quantidade as bigint), '')
		from tblprodutobarra pb
		left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
		left join tblunidademedida pe_um on (pe_um.codunidademedida = pe.codunidademedida)
		where pb.codprodutovariacao = pv.codprodutovariacao
		order by pe.quantidade nulls first, pb.barras
		limit 1
	),
	-- elpv_deposito.corredor, 
	-- elpv_deposito.prateleira, 
	-- elpv_deposito.coluna, 
	-- elpv_deposito.bloco,
    cast(es.saldoquantidade as bigint) as loja,
    cast(sld_uni.saldoquantidade as bigint) as loja_uni,
	elpv.estoqueminimo as min, 
	elpv.estoquemaximo as max, 
	cast(es_deposito.saldoquantidade as bigint) as deposito,
	cast((elpv.estoquemaximo * 1.5) - (case when coalesce(sld_uni.saldoquantidade, 0) <= 0 then 0 else sld_uni.saldoquantidade end) as bigint) as separar
from tblestoquelocalprodutovariacao elpv
left join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao)
inner join tblproduto p on (p.codproduto = pv.codproduto)
inner join tblmarca m on (m.codmarca = coalesce(pv.codmarca, p.codmarca))
inner join tblestoquelocalprodutovariacao elpv_deposito on (elpv_deposito.codestoquelocal = 101001 and elpv_deposito.codprodutovariacao = elpv.codprodutovariacao)
inner join tblestoquesaldo es_deposito on (es_deposito.codestoquelocalprodutovariacao = elpv_deposito.codestoquelocalprodutovariacao and es_deposito.fiscal = false)
inner join tblunidademedida um on (um.codunidademedida = p.codunidademedida)
left join (
        select elpv_uni.codprodutovariacao, sum(es_uni.saldoquantidade) as saldoquantidade
        from tblestoquelocalprodutovariacao elpv_uni
        inner join tblestoquesaldo es_uni on (es_uni.codestoquelocalprodutovariacao = elpv_uni.codestoquelocalprodutovariacao and es_uni.fiscal = false)
        where elpv_uni.codestoquelocal in (103001, 109001)
        --and elpv_uni.codprodutovariacao = 199
        group by elpv_uni.codprodutovariacao
    ) sld_uni on (sld_uni.codprodutovariacao = elpv.codprodutovariacao)
where elpv.codestoquelocal = 103001
--and m.marca not ilike 'gitex'
and m.controlada = true
--and coalesce(es.saldoquantidade, 0) <= (coalesce(elpv.estoqueminimo, 0) * 2)
and coalesce(sld_uni.saldoquantidade, 0) < (coalesce(elpv.estoquemaximo, 0) * 1.5)
and es_deposito.saldoquantidade > 0
--and es.saldoquantidade is null
order by m.marca, p.produto, pv.variacao

*/

select 
	-- m.marca, 
	p.codproduto, 
	-- p.produto, 
	-- pv.variacao, 
	p.produto || coalesce(' | ' || pv.variacao, ''),
	um.sigla,
	-- p.preco,
	--coalesce(pv.referencia, p.referencia), 
	(
		select pb.barras || coalesce(' ' || pe_um.sigla || ' C/' || cast(pe.quantidade as bigint), '')
		from tblprodutobarra pb
		left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
		left join tblunidademedida pe_um on (pe_um.codunidademedida = pe.codunidademedida)
		where pb.codprodutovariacao = pv.codprodutovariacao
		order by pe.quantidade nulls first, pb.barras
		limit 1
	),
	-- elpv_deposito.corredor, 
	-- elpv_deposito.prateleira, 
	-- elpv_deposito.coluna, 
	-- elpv_deposito.bloco,
    cast(es.saldoquantidade as bigint) as loja,
	elpv_antiga.estoqueminimo as min, 
	cast(elpv_antiga.estoqueminimo * 1.5 as int) as min_ext,
	elpv_antiga.estoquemaximo as max, 
	cast(elpv_antiga.estoquemaximo * 1.5 as int) as max_ext,
	cast(es_deposito.saldoquantidade as bigint) as deposito,
	cast((elpv_antiga.estoquemaximo * 1.5) - (case when coalesce(es.saldoquantidade, 0) <= 0 then 0 else es.saldoquantidade end) as bigint) as separar
from tblestoquelocalprodutovariacao elpv
left join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao)
inner join tblproduto p on (p.codproduto = pv.codproduto)
inner join tblmarca m on (m.codmarca = coalesce(pv.codmarca, p.codmarca))
inner join tblestoquelocalprodutovariacao elpv_deposito on (elpv_deposito.codestoquelocal = 101001 and elpv_deposito.codprodutovariacao = elpv.codprodutovariacao)
inner join tblestoquesaldo es_deposito on (es_deposito.codestoquelocalprodutovariacao = elpv_deposito.codestoquelocalprodutovariacao and es_deposito.fiscal = false)
inner join tblunidademedida um on (um.codunidademedida = p.codunidademedida)
left join tblestoquelocalprodutovariacao elpv_antiga on (elpv_antiga.codestoquelocal = 103001 and elpv_antiga.codprodutovariacao = elpv.codprodutovariacao)
where elpv.codestoquelocal = 109001
--and m.marca not ilike 'gitex'
and m.controlada = true
and coalesce(es.saldoquantidade, 0) <= (coalesce(elpv.estoqueminimo, 0) * 1.5)
--and coalesce(es.saldoquantidade, 0) < (coalesce(elpv_antiga.estoquemaximo, 0) * 1.5)
and es_deposito.saldoquantidade > 0
--and es.saldoquantidade is null
order by m.marca, p.produto, pv.variacao

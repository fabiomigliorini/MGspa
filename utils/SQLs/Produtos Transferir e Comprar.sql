-- alter table tblmarca add controlada boolean not null default false

-- update tblmarca set controlada = true where marca ilike 'Xpto'

-- PARA SEPARAR
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
	elpv.estoqueminimo as min, 
	elpv.estoquemaximo as max, 
	cast(es_deposito.saldoquantidade as bigint) as deposito,
	cast(elpv.estoquemaximo - (case when es.saldoquantidade < 0 then 0 else es.saldoquantidade end) as bigint) as separar
from tblestoquelocalprodutovariacao elpv
inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao)
inner join tblproduto p on (p.codproduto = pv.codproduto)
inner join tblmarca m on (m.codmarca = coalesce(pv.codmarca, p.codmarca))
inner join tblestoquelocalprodutovariacao elpv_deposito on (elpv_deposito.codestoquelocal = 101001 and elpv_deposito.codprodutovariacao = elpv.codprodutovariacao)
inner join tblestoquesaldo es_deposito on (es_deposito.codestoquelocalprodutovariacao = elpv_deposito.codestoquelocalprodutovariacao and es_deposito.fiscal = false)
inner join tblunidademedida um on (um.codunidademedida = p.codunidademedida)
where elpv.codestoquelocal = 102001
and m.controlada = true
and es.saldoquantidade < elpv.estoqueminimo
and es_deposito.saldoquantidade > 0
order by m.marca, p.produto, pv.variacao

/*
-- PARA COMPRAR
select 
	--m.marca,
	p.codproduto,
	--p.produto,
	--pv.variacao,
	p.produto || coalesce(' | ' || pv.variacao, '') as produto,
	coalesce(pv.referencia, p.referencia) as referencia,
	--p.preco,
	pv.custoultimacompra as custo,
	pv.dataultimacompra as data,
	cast(sld.saldoquantidade as bigint) as sld,
	cast(case when sld.vendadiaquantidadeprevisao != 0 then sld.saldoquantidade / sld.vendadiaquantidadeprevisao else null end as bigint) as dias,
	sld.estoqueminimo as min,
	sld.estoquemaximo as max,
	cast(sld.estoquemaximo - case when sld.saldoquantidade > 0 then sld.saldoquantidade else 0 end as bigint) as comprar
from tblproduto p
inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
inner join tblmarca m on (m.codmarca = coalesce(pv.codmarca, p.codmarca))
inner join (
	select 
		elpv.codprodutovariacao
		, sum(elpv.estoqueminimo) as estoqueminimo
		, sum(elpv.estoquemaximo) as estoquemaximo
		, sum(es.saldoquantidade) as saldoquantidade
		, sum(es.saldovalor) as saldovalor
		, sum(elpv.vendadiaquantidadeprevisao) as vendadiaquantidadeprevisao
	from tblestoquelocalprodutovariacao elpv
	left join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
	group by elpv.codprodutovariacao
	--limit 50
	) sld on (sld.codprodutovariacao = pv.codprodutovariacao)
where p.inativo is null
and m.controlada = true
and coalesce(sld.saldoquantidade, 0) < sld.estoquemaximo
and m.marca not ilike '%acrilex%'
order by m.marca, p.produto, pv.variacao

*/

select 
	-- m.marca, 
	p.codproduto, 
	-- p.produto, 
	-- pv.variacao, 
	p.produto || coalesce(' | ' || pv.variacao, '') as produto,
	um.sigla as um,
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
	) as barras,
	array_to_string(array(
		select cast(pe.quantidade as bigint)
		from tblprodutoembalagem pe 
		left join tblunidademedida pe_um on (pe_um.codunidademedida = pe.codunidademedida)
		where pe.codproduto = pv.codproduto
		order by pe.quantidade nulls first
		--limit 1
	), '/') AS emb,	
	-- elpv_deposito.corredor, 
	-- elpv_deposito.prateleira, 
	-- elpv_deposito.coluna, 
	-- elpv_deposito.bloco,
	cast(es.saldoquantidade as bigint) as loja,
	elpv.estoqueminimo as min, 
	elpv.estoquemaximo as max, 
	cast(es_deposito.saldoquantidade as bigint) as deposito,
	cast((coalesce(elpv.estoquemaximo, 1)) - (case when coalesce(es.saldoquantidade, 0) <= 0 then 0 else es.saldoquantidade end) as bigint) as separar
	--cast((elpv.estoquemaximo * 2) - (case when coalesce(es.saldoquantidade, 0) <= 0 then 0 else es.saldoquantidade end) as bigint) as separar
from tblproduto p 
inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
inner join tblmarca m on (m.codmarca = coalesce(pv.codmarca, p.codmarca))
inner join tblestoquelocalprodutovariacao elpv_deposito on (elpv_deposito.codestoquelocal = 101001 and elpv_deposito.codprodutovariacao = pv.codprodutovariacao)
inner join tblestoquesaldo es_deposito on (es_deposito.codestoquelocalprodutovariacao = elpv_deposito.codestoquelocalprodutovariacao and es_deposito.fiscal = false)
inner join tblunidademedida um on (um.codunidademedida = p.codunidademedida)
left join tblestoquelocalprodutovariacao elpv on (elpv.codestoquelocal = 102001 and elpv.codprodutovariacao = pv.codprodutovariacao)
left join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
where coalesce(es_deposito.saldoquantidade, 0) > 0
and coalesce(es.saldoquantidade, 0) <= coalesce(elpv.estoqueminimo, 1)
and coalesce(es.saldoquantidade, 0) < coalesce(elpv.estoquemaximo, 1)
and m.controlada = true
--and m.marca ilike 'escurra'
--and m.marca not ilike 'acrilex'
--and m.marca not ilike 'delta'
--and m.marca not ilike 'henkel'
--and es.saldoquantidade is null
order by m.marca, p.produto, pv.variacao

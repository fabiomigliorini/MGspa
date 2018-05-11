
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
	cast((elpv.estoquemaximo) - (case when coalesce(es.saldoquantidade, 0) <= 0 then 0 else es.saldoquantidade end) as bigint) as separar
	--cast((elpv.estoquemaximo * 2) - (case when coalesce(es.saldoquantidade, 0) <= 0 then 0 else es.saldoquantidade end) as bigint) as separar
from tblestoquelocalprodutovariacao elpv
left join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao)
inner join tblproduto p on (p.codproduto = pv.codproduto)
inner join tblmarca m on (m.codmarca = coalesce(pv.codmarca, p.codmarca))
inner join tblestoquelocalprodutovariacao elpv_deposito on (elpv_deposito.codestoquelocal = 101001 and elpv_deposito.codprodutovariacao = elpv.codprodutovariacao)
inner join tblestoquesaldo es_deposito on (es_deposito.codestoquelocalprodutovariacao = elpv_deposito.codestoquelocalprodutovariacao and es_deposito.fiscal = false)
inner join tblunidademedida um on (um.codunidademedida = p.codunidademedida)
where elpv.codestoquelocal = 104001
--and m.marca ilike 'acrilex'
--and m.marca ilike 'delta'
and m.marca ilike 'henkel'
--and m.controlada = true
--and coalesce(es.saldoquantidade, 0) <= coalesce(elpv.estoqueminimo, 0)
--and coalesce(es.saldoquantidade, 0) < coalesce(elpv.estoquemaximo, 0)
and es_deposito.saldoquantidade > 0
and es_deposito.saldoquantidade > elpv_deposito.estoquemaximo
--and p.produto
order by m.marca, p.produto, pv.variacao
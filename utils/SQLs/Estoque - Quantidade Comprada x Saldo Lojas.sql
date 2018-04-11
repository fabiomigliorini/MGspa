with compra as (
	select 
		p.codproduto, 
		p.produto, 
		pb.codprodutovariacao, 
		pv.variacao, 
		sum(npb.quantidade * coalesce(pe.quantidade, 1)) as quantidade
	from tblnegocioprodutobarra npb
	inner join tblprodutobarra pb on (pb.codprodutobarra = npb.codprodutobarra)
	inner join tblprodutovariacao pv on (pv.codprodutovariacao = pb.codprodutovariacao)
	left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
	inner join tblproduto p on (p.codproduto = pv.codproduto)
	where npb.codnegocio = 1063937
	group by
		p.codproduto, 
		p.produto, 
		pb.codprodutovariacao, 
		pv.variacao
),
dep as (
	select elpv.codprodutovariacao, es.saldoquantidade
	from tblestoquelocalprodutovariacao elpv
	inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
	where elpv.codestoquelocal = 101001
),
bot as (
	select elpv.codprodutovariacao, es.saldoquantidade
	from tblestoquelocalprodutovariacao elpv
	inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
	where elpv.codestoquelocal = 102001
),
cen as (
	select elpv.codprodutovariacao, es.saldoquantidade
	from tblestoquelocalprodutovariacao elpv
	inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
	where elpv.codestoquelocal = 103001
),
imp as (
	select elpv.codprodutovariacao, es.saldoquantidade
	from tblestoquelocalprodutovariacao elpv
	inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
	where elpv.codestoquelocal = 104001
)
select 
	compra.codproduto,
	compra.produto,
	compra.codprodutovariacao,
	compra.variacao,
	array_to_string(array(
		select pb.barras || coalesce(' ' || pe_um.sigla || ' C/' || cast(pe.quantidade as bigint), '')
		from tblprodutobarra pb
		left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
		left join tblunidademedida pe_um on (pe_um.codunidademedida = pe.codunidademedida)
		where pb.codprodutovariacao = compra.codprodutovariacao
		order by pe.quantidade nulls first, pb.barras
	), '/') AS barras,
	compra.quantidade as compra,
	dep.saldoquantidade as deposito,
	bot.saldoquantidade as botanico,
	cen.saldoquantidade as centro,
	imp.saldoquantidade as imperial
from compra
inner join dep on (dep.codprodutovariacao = compra.codprodutovariacao)
inner join bot on (bot.codprodutovariacao = compra.codprodutovariacao)
inner join cen on (cen.codprodutovariacao = compra.codprodutovariacao)
inner join imp on (imp.codprodutovariacao = compra.codprodutovariacao)
order by compra.produto, compra.codproduto, compra.variacao, compra.codprodutovariacao

/*
select es.saldoquantidade
from tblestoquelocalprodutovariacao elpv
inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
where elpv.codestoquelocal = 101001
and elpv.codprodutovariacao = 80410
*/
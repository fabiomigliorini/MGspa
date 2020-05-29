
with tr as (
	with 
	/*
	dep as (
		select 
			pv.codproduto,
			pv.codprodutovariacao, 
			sum(case when es.fiscal != true then es.saldoquantidade else null end) as fisico,
			sum(case when es.fiscal = true then es.saldoquantidade else null end) as fiscal,
			sum(case when es.fiscal = true then 1 else -1 end * es.saldoquantidade) as sobra,
			null
		from tblproduto p
		inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
		inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao and elpv.codestoquelocal = 101001)
		inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
		--where p.codproduto = 100
		group by pv.codprodutovariacao
	),
	*/ 
	var as (
		select 
			pv.codproduto,
			pv.codprodutovariacao, 
			round(sum(case when es.fiscal != true then es.saldoquantidade else null end), 0) as fisico,
			round(sum(case when es.fiscal = true then es.saldoquantidade else null end), 0) as fiscal,
			round(sum(case when es.fiscal = true then 1 else -1 end * es.saldoquantidade), 0) as transferir
		from tblproduto p
		inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
		inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao and elpv.codestoquelocal = 104001)
		inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
		--where p.codproduto = 100
		group by pv.codprodutovariacao
		having sum(case when es.fiscal != true then es.saldoquantidade else null end) >= 0
		and round(sum(case when es.fiscal = true then 1 else -1 end * es.saldoquantidade), 0) > 0
	),
	prod as (
		select 
			pv.codproduto,
			round(p.preco * 0.7, 2) as valor,
			round(sum(case when es.fiscal = true then 1 else -1 end * es.saldoquantidade), 0) as transferir
		from tblproduto p
		inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
		inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao and elpv.codestoquelocal = 104001)
		inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
		--where p.codproduto = 100
		where p.codtributacao = 1 
		group by pv.codproduto, p.preco
		having sum(case when es.fiscal != true then es.saldoquantidade else null end) = 0
		and round(sum(case when es.fiscal = true then 1 else -1 end * es.saldoquantidade), 0) > 0
	)
	select 
		prod.codproduto, 
		var.codprodutovariacao, 
		(select min(pb.codprodutobarra) from tblprodutobarra pb where pb.codprodutovariacao = var.codprodutovariacao and pb.codprodutoembalagem is null) as codprodutobarra,
		case when (prod.transferir > var.transferir) then var.transferir else prod.transferir end as transferir,
		prod.valor
	from prod
	inner join var on  (var.codproduto = prod.codproduto)
	order by codproduto 
	limit 500 
)
insert into tblnotafiscalprodutobarra (
	codnotafiscal,
	codprodutobarra,
	codcfop,
	quantidade,
	valorunitario,
	valortotal,
	icmsbase,
	icmspercentual,
	icmsvalor,
	alteracao,
	codusuarioalteracao,
	criacao,
	codusuariocriacao,
	icmscst,
	ipicst,
	piscst,
	cofinscst,
	certidaosefazmt,
	icmsbasepercentual
	)
select 
	--codproduto,
	-1 as codnotafiscal,
	tr.codprodutobarra,
	5152 as codcfop,
	tr.transferir as quantidade,
	tr.valor as valorunitario,
	tr.valor * tr.transferir as valortotal,
	tr.valor * tr.transferir as icmsbase,
	17 as icmspercentual,
	round(tr.valor * tr.transferir * 0.17, 2) as icmsvalor,
	date_trunc('second', now()) as alteracao,
	1 as codusuarioalteracao,
	date_trunc('second', now()) as criacao,
	1 as codusuariocriacao,
	0 as icmscst,
	99 as ipicst,
	8 as piscst,
	8 as cofinscst,
	false as certidaosefazmt,
	100 as icmsbasepercentual
from tr
where tr.codprodutobarra is not null
with venda as (
	select elpv.codprodutovariacao, sum(elpvv.quantidade) as venda
	from tblestoquelocalprodutovariacao elpv 
	inner join tblestoquelocalprodutovariacaovenda elpvv on (elpv.codestoquelocalprodutovariacao = elpvv.codestoquelocalprodutovariacao)
	where elpvv.mes >= date_trunc('month', now() - interval '11 months')
	group by elpv.codprodutovariacao
--	limit 50
), saldo as (
	select elpv.codprodutovariacao, sum(es.saldoquantidade) as saldo
	from tblestoquelocalprodutovariacao elpv 
	inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
	where elpv.codestoquelocal in (101001, 102001, 103001, 104001)
	group by elpv.codprodutovariacao
--	limit 50
)
select 
	pv.codproduto
	, p.produto || coalesce(' | ' || pv.variacao, '') as produto
	, pv.dataultimacompra as compra
	, s.saldo
	, v.venda
	, round((s.saldo / case when coalesce(v.venda, 0) = 0 then 1 else v.venda end), 1) as anos
	--, pv.*
from tblproduto p 
inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
inner join saldo s on (s.codprodutovariacao = pv.codprodutovariacao)
left join venda v on (v.codprodutovariacao = pv.codprodutovariacao)
where p.codmarca in (select m.codmarca from tblmarca m where m.marca ilike '%acrilex%')
and s.saldo > 0
and coalesce(pv.dataultimacompra, '2000-01-01') < (now() - interval '1 year')
and s.saldo > coalesce(v.venda, 0)
order by p.produto, pv.variacao nulls first, pv.codprodutovariacao


/*
select * 
from tblestoquelocalprodutovariacao elpv
left join tblestoquelocalprodutovariacaovendas elpvv on (elpvv.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
where elpv.codestoquelocal = 101001
*/
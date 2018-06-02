with vendas as (
	select elpv.codprodutovariacao, sum(elpvv.quantidade) as quantidade
	from tblestoquelocalprodutovariacao elpv
	inner join tblestoquelocalprodutovariacaovenda elpvv on (elpvv.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
	where elpvv.mes >= date_trunc('month', now()) - interval '1 year'
	group by elpv.codprodutovariacao
)
select p.codproduto, pv.codprodutovariacao, p.produto, pv.variacao, v.quantidade
from tblproduto p
inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
left join vendas v on (v.codprodutovariacao = pv.codprodutovariacao)
where p.inativo is null
and pv.inativo is null
and pv.descontinuado is null
and p.codmarca in (select m.codmarca from tblmarca m where m.marca ilike '%gitex%')
order by v.quantidade desc

with abc as (
	select 
		p.codproduto, 
		p.produto, 
		sum(case when n.codoperacao = 1 then -1 else 1 end * npb.valortotal) as venda, 
		sum(case when n.codoperacao = 1 then -1 else 1 end) as numerovendas
	from tblnegocio n
	inner join tblnegocioprodutobarra npb on (npb.codnegocio = n.codnegocio)
	inner join tblnaturezaoperacao no on (no.codnaturezaoperacao = n.codnaturezaoperacao)
	inner join tblprodutobarra pb on (pb.codprodutobarra = npb.codprodutobarra)
	inner join tblproduto p on (p.codproduto = pb.codproduto)
	where n.lancamento >= now() - '1 year':: interval
	and n.codnegociostatus = 2
	and n.codfilial in (101,102,103,104)
	and (no.venda = true or no.vendadevolucao = true)
	and p.codsubgrupoproduto not in (2951)
	--and p.codproduto = 33385
	group by p.codproduto, p.produto
	--order by lancamento asc
	--limit 100
)
select * 
from abc
where numerovendas >= 10
order by venda desc


select p.pessoa, p.fantasia, to_tsvector('portuguese', p.pessoa || p.fantasia), to_tsvector('english', p.pessoa || p.fantasia) 
from tblpessoa p
where to_tsvector('portuguese', p.pessoa || p.fantasia) @@ phraseto_tsquery('portuguese', 'migliorini')
and to_tsvector('portuguese', p.pessoa || p.fantasia) @@ phraseto_tsquery('portuguese', 'fabio')


	
with prod as (
	select 
		p.codproduto, 
		pv.codprodutovariacao,
		pb.codprodutobarra,
		pb.barras,
		p.produto || coalesce(' ' || pv.variacao, '') || coalesce(' ' || pb.variacao, '') || coalesce(' C/' || cast(pe.quantidade as bigint), '') as produto,
		coalesce(p.referencia, '') || coalesce(pv.referencia, '')  || coalesce(pb.referencia, '') as referencia,
		coalesce(pe.preco, p.preco * coalesce(pe.quantidade, 1)) as preco,
		coalesce(pv.inativo, p.inativo) as inativo,
		pv.descontinuado
	from tblproduto p
	inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
	inner join tblprodutobarra pb on (pb.codprodutovariacao  = pv.codprodutovariacao and pb.codproduto = p.codproduto)
	left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
	where pe.codprodutoembalagem is not null
)
select * 
from prod p
where p.produto || ' ' || p.referencia ilike '%copimax%'
and p.produto || ' ' || p.referencia ilike '%papel%'
and p.produto || ' ' || p.referencia ilike '%a4%'
and p.inativo is null
order by p.produto, p.referencia, p.preco, p.codprodutobarra



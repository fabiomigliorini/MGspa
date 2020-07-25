
select p.pessoa, p.fantasia, to_tsvector('portuguese', p.pessoa || p.fantasia), to_tsvector('english', p.pessoa || p.fantasia) 
from tblpessoa p
where to_tsvector('portuguese', p.pessoa || p.fantasia) @@ phraseto_tsquery('portuguese', 'migliorini')
and to_tsvector('portuguese', p.pessoa || p.fantasia) @@ phraseto_tsquery('portuguese', 'fabio')

with prod as (
	select 
		p.codproduto, 
		p.produto || coalesce(' ' || pv.variacao, '') || coalesce(' ' || pbv.variacao, ''),
		pv.variacao, 
		pb.variacao, 
		coalesce(p.referencia, '') || coalesce(pv.referencia, '')  || coalesce(pb.referencia, '') as referencia
	from tblproduto p
	inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
	inner join tblprodutobarra pb on (pb.codprodutovariacao  = pv.codprodutovariacao and pb.codproduto = p.codproduto)
	left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
)
select * 
from prod p
limit 100


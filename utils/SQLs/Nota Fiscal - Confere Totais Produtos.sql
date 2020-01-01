with tot as (
	select nfpb.codnotafiscal, sum(nfpb.valortotal) as total
	from tblnotafiscalprodutobarra nfpb
	group by nfpb.codnotafiscal
) 
select 
	nf.codnotafiscal,
	nf.valorprodutos,
	tot.total,
	nf.*
from tblnotafiscal nf
left join tot on (tot.codnotafiscal = nf.codnotafiscal)
where nf.valorprodutos != tot.total
limit 50
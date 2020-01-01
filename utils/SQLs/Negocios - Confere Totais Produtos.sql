with tot as (
	select nfpb.codnegocio, sum(nfpb.valortotal) as total
	from tblnegocioprodutobarra nfpb
	group by nfpb.codnegocio
) 
select 
	nf.codnegocio,
	nf.valorprodutos,
	tot.total,
	nf.*
from tblnegocio nf
left join tot on (tot.codnegocio = nf.codnegocio)
where nf.valorprodutos != coalesce(tot.total, 0)
--limit 50
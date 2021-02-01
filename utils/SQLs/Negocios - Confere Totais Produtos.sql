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
ORDER BY lancamento desc
--limit 50

/*
update tblnegocioprodutobarra set valortotal = valortotal where codnegocio = 1998179

update tblnegocio set valorprodutos = 0, valordesconto = null where codnegocio = 10236046

commit
*/

update tblnegocio set codnegociostatus  = 2 where codnegocio = 2137384
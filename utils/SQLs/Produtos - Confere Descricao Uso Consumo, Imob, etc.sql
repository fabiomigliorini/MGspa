/*
select distinct codtipoproduto from tblproduto 

select * from tbltipoproduto
*/
/*
with prods as (
	select 
		p.codproduto, 
		p.codtipoproduto, 
		p.produto, 
		p.referencia, 
		n.ncm, 
		substr(lpad(n.ncm, 8, '0'), 1, 4) || '.' || substr(lpad(n.ncm, 8, '0'), 5, 2) || '.' || substr(lpad(n.ncm, 8, '0'), 7, 2) as ncmformatado		
	from tblproduto p
	inner join tblncm n on (n.codncm = p.codncm)
	where p.codtipoproduto = 7
)
select * 
from prods p
--where p.produto not ilike '# Outros Insumos Diversos%'
--where p.produto not ilike '# Imobilizado Diversos %'
--where p.produto not ilike '# Uso consumo Diversos %'
--where p.produto not ilike '%' || p.ncm || '%'
--where p.referencia not ilike '%' || p.ncmformatado || '%'

--update tblproduto set produto = replace(produto, 'Imobilizado', 'Uso Consumo') where produto ilike '# Imobilizado Diversos %' and codtipoproduto = 7
--update tblproduto set produto = replace(produto, '.', '') where produto ilike '%.%' and codtipoproduto = 7
*/

	select 
		p.codtipoproduto, 
		n.ncm, 
		count(*),
		min(p.codproduto) as min,
		max(p.codproduto) as max
	from tblproduto p
	inner join tblncm n on (n.codncm = p.codncm)
	where p.codtipoproduto in (4, 10, 8, 7)
	group by p.codtipoproduto, 
		n.ncm
	having count(*) > 1 
	
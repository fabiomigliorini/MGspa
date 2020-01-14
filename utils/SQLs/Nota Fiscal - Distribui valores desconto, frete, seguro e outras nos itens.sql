/*
-- joga nos itens proporcionalmente
with totais as (
	select nf.codnotafiscal,  nf.valoroutras,  nf.valoroutras / nf.valorprodutos as percentualoutras, sum(nfpb.valoroutras)
	from tblnotafiscalbackup nf
	inner join tblnotafiscalprodutobarra nfpb on (nfpb.codnotafiscal = nf.codnotafiscal)
	--where nf.codnotafiscal between 500001 and 900000
	where nf.valorprodutos != 0
	group by nf.codnotafiscal, nf.valoroutras, nf.valorprodutos 
	having coalesce(sum(nfpb.valoroutras), 0) != nf.valoroutras
	limit 1000
)
update tblnotafiscalprodutobarra
set valoroutras = tblnotafiscalprodutobarra.valortotal * totais.percentualoutras
from totais
where tblnotafiscalprodutobarra.codnotafiscal = totais.codnotafiscal
returning tblnotafiscalprodutobarra.codnotafiscal

-- se sobrar diferenca, joga no de maior valor
with corrigir as (
	select 
		a.codnotafiscal, 
		b.valoroutras, 
		a.valoroutras, 
		b.valoroutras - a.valoroutras as dif,
		(
			select i.codnotafiscalprodutobarra
			from tblnotafiscalprodutobarra i
			where i.codnotafiscal = b.codnotafiscal
			order by i.valortotal DESC
			limit 1
		) as codnotafiscalprodutobarra
	from tblnotafiscalbackup b
	inner join tblnotafiscal a on (a.codnotafiscal = b.codnotafiscal)
	where b.valoroutras != a.valoroutras
	limit 20000
)
update tblnotafiscalprodutobarra 
set valoroutras = tblnotafiscalprodutobarra.valoroutras + corrigir.dif
from corrigir
where tblnotafiscalprodutobarra.codnotafiscalprodutobarra = corrigir.codnotafiscalprodutobarra
returning tblnotafiscalprodutobarra.codnotafiscal, tblnotafiscalprodutobarra.codnotafiscalprodutobarra
*/

-- Confere se tudo bateu com backup
select b.codnotafiscal
from tblnotafiscalbackup b
inner join tblnotafiscal a on (a.codnotafiscal = b.codnotafiscal)
where coalesce(a.valordesconto, 0) != coalesce(b.valordesconto, 0)
or coalesce(a.valorfrete, 0) != coalesce(b.valorfrete, 0)
or coalesce(a.valorseguro, 0) != coalesce(b.valorseguro, 0)
or coalesce(a.valoroutras, 0) != coalesce(b.valoroutras, 0)







with itens as (
	with corrigir as  (
		select nt.codnfeterceiro, nti.codnfeterceiroitem, nt.codnotafiscal, nti.codprodutobarra, nti.vbc, nti.vicms, nti.picms, nti.vprod
		from tblnfeterceiro nt
		inner join tblnfeterceiroitem nti on (nti.codnfeterceiro = nt.codnfeterceiro)
		inner join tblnotafiscal nf on (nf.codnotafiscal = nt.codnotafiscal)
		--
		where coalesce(nti.vicms, 0) != 0
		and extract(year from nt.emissao) = 2020
		order by nt.emissao desc
	)
	select nfpb.codnotafiscalprodutobarra, nfpb.icmsvalor, c.vbc, c.vicms, c.picms
	from corrigir c 
	inner join tblnotafiscalprodutobarra nfpb on (nfpb.codnotafiscal = C.codnotafiscal and nfpb.codprodutobarra = C.codprodutobarra and nfpb.valortotal = c.vprod)
	where coalesce(nfpb.icmsvalor, 0) = 0
)
update tblnotafiscalprodutobarra 
set icmsvalor = itens.vicms
, icmsbase = itens.vbc
, icmspercentual = itens.picms
from itens 
where tblnotafiscalprodutobarra.codnotafiscalprodutobarra = itens.codnotafiscalprodutobarra
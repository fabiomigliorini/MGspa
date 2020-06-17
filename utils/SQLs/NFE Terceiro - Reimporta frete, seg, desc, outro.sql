/*
update tblnotafiscalprodutobarra set valordesconto = null where codnotafiscal = 1619228

-- update tblnotafiscalprodutobarra set valordesconto = (682.57 / 4425.63) * valortotal where valordesconto is null and codnotafiscal = 1619228
update tblnotafiscalprodutobarra set valordesconto = valortotal * 0.1 where valordesconto is null and codnotafiscal = 1619228
*/

with orig as (
	select 
		nfpb.codnotafiscalprodutobarra, nfpb.codnotafiscal, nti.*
	from tblnotafiscalprodutobarra nfpb
	inner join tblnfeterceiro nt on (nt.codnotafiscal = nfpb.codnotafiscal)
	inner join tblnfeterceiroitem nti on (nti.codnfeterceiro = nt.codnfeterceiro and nfpb.codprodutobarra = nti.codprodutobarra and nti.vprod = nfpb.valortotal)
	where nfpb.codnotafiscal = 1638870
)
update tblnotafiscalprodutobarra 
set valorfrete = orig.vfrete
, valorseguro = orig.vseg
, valordesconto = orig.vdesc
, valoroutras = orig.voutro
, icmsvalor = orig.vicms
, icmsbase
from orig
where tblnotafiscalprodutobarra.codnotafiscalprodutobarra = orig.codnotafiscalprodutobarra

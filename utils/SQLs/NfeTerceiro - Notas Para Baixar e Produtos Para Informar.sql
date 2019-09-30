-- Notas para baixar XML
select n.codnfeterceiro, n.nfechave, n.emitente
from tblnfeterceiro n
left join tblnfeterceiroitem i on (i.codnfeterceiro = n.codnfeterceiro)
where n.codnotafiscal is null
and n.ignorada = false
and coalesce(n.indmanifestacao, 0) not in (210240, 210220)
and coalesce(n.indsituacao, 0) not in (2, 3)
and i.codnfeterceiro is null
order by n.codnfeterceiro

-- Notas para informar Itens
select n.codnfeterceiro, n.emitente, i.cprod, i.xprod, i.ceantrib
from tblnfeterceiro n
left join tblnfeterceiroitem i on (i.codnfeterceiro = n.codnfeterceiro)
where n.codnotafiscal is null
and n.ignorada = false
and coalesce(n.indmanifestacao, 0) not in (210240, 210220)
and coalesce(n.indsituacao, 0) not in (2, 3)
and i.codprodutobarra is null
order by n.emitente, n.codnfeterceiro, i.nitem

-- Notas para importar que desconto nao bate com itens
select n.codnfeterceiro, n.nfechave, n.emitente, coalesce(valordesconto, 0)
from tblnfeterceiro n
where n.codnotafiscal is null
and n.ignorada = false
and coalesce(n.indmanifestacao, 0) not in (210240, 210220)
and coalesce(n.indsituacao, 0) not in (2, 3)
and coalesce(valordesconto, 0)  > 0
order by n.codnfeterceiro
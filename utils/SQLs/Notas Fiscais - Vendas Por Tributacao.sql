/*
-- VENDA Por Tributacao
select 
	date_trunc('month', emissao) as mes,
	t.tributacao,
	sum(nfpb.valortotal) as valor
from tblnotafiscal nf
inner join tblnaturezaoperacao no on (no.codnaturezaoperacao = nf.codnaturezaoperacao)
inner join tblnotafiscalprodutobarra nfpb on (nfpb.codnotafiscal = nf.codnotafiscal)
inner join tblprodutobarra pb on (pb.codprodutobarra = nfpb.codprodutobarra)
inner join tblproduto p on (p.codproduto = pb.codproduto)
inner join tbltributacao t on (t.codtributacao = p.codtributacao)
where nf.emissao >= '2018-01-01'
and nf.emitida = true
and nf.nfeinutilizacao is null
and nf.nfecancelamento is null
and nf.nfeautorizacao is not null
and nf.codfilial in (101, 102, 103, 104)
and no.venda = true
group by 
	date_trunc('month', emissao),
	t.tributacao
limit 100

-- Média Ponderada dos MVA's
select 
	date_trunc('year', emissao) as ano,
	t.tributacao,
	sum(nfpb.valortotal * c.mva) / sum(nfpb.valortotal) as mva
from tblnotafiscal nf
inner join tblnaturezaoperacao no on (no.codnaturezaoperacao = nf.codnaturezaoperacao)
inner join tblnotafiscalprodutobarra nfpb on (nfpb.codnotafiscal = nf.codnotafiscal)
inner join tblprodutobarra pb on (pb.codprodutobarra = nfpb.codprodutobarra)
inner join tblproduto p on (p.codproduto = pb.codproduto)
inner join tbltributacao t on (t.codtributacao = p.codtributacao)
inner join tblcest c on (c.codcest = p.codcest)
where nf.emissao >= '2018-01-01'
and nf.emitida = true
and nf.nfeinutilizacao is null
and nf.nfecancelamento is null
and nf.nfeautorizacao is not null
and nf.codfilial in (101, 102, 103, 104)
and no.venda = true
and c.mva is not null
group by 
	date_trunc('year', emissao),
	t.tributacao
limit 100
*/

-- VENDA Por Tributacao dos BITS
select 
	date_trunc('month', emissao) as mes,
	t.tributacao,
	sum(nfpb.valortotal) as valor
from tblnotafiscal nf
inner join tblnaturezaoperacao no on (no.codnaturezaoperacao = nf.codnaturezaoperacao)
inner join tblnotafiscalprodutobarra nfpb on (nfpb.codnotafiscal = nf.codnotafiscal)
inner join tblprodutobarra pb on (pb.codprodutobarra = nfpb.codprodutobarra)
inner join tblproduto p on (p.codproduto = pb.codproduto)
inner join tbltributacao t on (t.codtributacao = p.codtributacao)
where nf.emissao >= '2018-01-01'
and nf.emitida = true
and nf.nfeinutilizacao is null
and nf.nfecancelamento is null
and nf.nfeautorizacao is not null
and nf.codfilial in (101, 102, 103, 104)
and no.venda = true
and p.bit = true
group by 
	date_trunc('month', emissao),
	t.tributacao
limit 100

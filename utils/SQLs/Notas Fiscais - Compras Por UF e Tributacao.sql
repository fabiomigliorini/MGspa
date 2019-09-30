select date_trunc('month', nf.saida) as mes, t.tributacao, e.sigla as uf, sum(nfpb.valortotal) as valor, sum(nfpb.icmsstvalor) as st
from tblnotafiscal nf
inner join tblpessoa p on (p.codpessoa = nf.codpessoa)
inner join tblcidade c on (c.codcidade = p.codcidade)
inner join tblestado e on (e.codestado = c.codestado)
inner join tblnotafiscalprodutobarra nfpb on (nfpb.codnotafiscal = nf.codnotafiscal)
inner join tblprodutobarra pb on (pb.codprodutobarra = nfpb.codprodutobarra)
inner join tblproduto pr on (pr.codproduto = pb.codproduto)
inner join tbltributacao t on (t.codtributacao = pr.codtributacao)
where nf.codnaturezaoperacao = 00000004
and nf.saida >= '2016-01-01'
and nf.codfilial in (101, 102, 103, 104)
group by 1, 2, 3
order by 1, 2, 3
--limit 10

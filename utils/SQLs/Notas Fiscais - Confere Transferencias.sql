select nf.emitida, no.naturezaoperacao, sum(nf.valortotal) as valortotal, count(*) as notas,  max(nf.codnotafiscal) as codnotafiscal
from tblnotafiscal nf
inner join tblnaturezaoperacao no on (no.codnaturezaoperacao = nf.codnaturezaoperacao)
inner join tblfilial f on (f.codfilial = nf.codfilial)
where nf.saida >= (NOW() - '100 Days'::interval)
and nf.codpessoa in (select distinct f2.codpessoa from tblfilial f2 where f2.codempresa = f.codempresa)
and nf.nfeinutilizacao is null
and nf.nfecancelamento is null
group by nf.emitida, no.naturezaoperacao
order by 1 desc, 2
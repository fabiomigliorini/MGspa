select 
	date_trunc('month', nf.saida), 
	no.codoperacao,
	no.naturezaoperacao,
	sum(nf.valortotal)
--	*
from tblnotafiscal nf
inner join tblnaturezaoperacao no ON (no.codnaturezaoperacao = nf.codnaturezaoperacao)
where nf.saida between '2019-01-01' and '2019-08-31 23:59:59'
and nf.nfecancelamento is null
and nf.nfeinutilizacao is null
group by
	date_trunc('month', nf.saida),
	no.codoperacao,
	no.naturezaoperacao

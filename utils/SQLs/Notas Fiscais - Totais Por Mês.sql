-- valor por mes
select 
	date_trunc('month', nf.saida), 
	no.codoperacao,
	no.naturezaoperacao,
	sum(nf.valortotal)
--	*
from tblnotafiscal nf
inner join tblnaturezaoperacao no ON (no.codnaturezaoperacao = nf.codnaturezaoperacao)
where nf.saida between '2019-01-01' and '2019-12-31 23:59:59'
and nf.nfecancelamento is null
and nf.nfeinutilizacao is null
group by
	date_trunc('month', nf.saida),
	no.codoperacao,
	no.naturezaoperacao

-- quantidade por mes
select 
	date_trunc('month', nf.emissao), 
	--nf.modelo,
	count(*)
from tblnotafiscal nf
group by
	date_trunc('month', nf.emissao)
	--, nf.modelo
order by 1 desc, 2 asc

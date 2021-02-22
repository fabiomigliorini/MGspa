-- valor por mes
select 
	date_trunc('month', nf.saida), 
	nf.codfilial,
	sum(case when no.codoperacao = 1 then nf.icmsvalor else null end) as entrada,
	sum(case when no.codoperacao != 1 then nf.icmsvalor else null end) as saida,
	sum(case when no.codoperacao != 1 then 1 else -1 end * nf.icmsvalor) as saldo
from tblnotafiscal nf
inner join tblnaturezaoperacao no ON (no.codnaturezaoperacao = nf.codnaturezaoperacao)
where nf.saida between '2021-01-01' and '2021-01-31 23:59:59'
and nf.nfecancelamento is null
and nf.nfeinutilizacao is null
and nf.codfilial between 101 and 104
group by
	date_trunc('month', nf.saida),
	nf.codfilial

	
	
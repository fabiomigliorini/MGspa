select distinct
	orig.codestoquemes
	, dest.codestoquemes
	, orig.saidaquantidade
	, orig.saidavalor
	, dest.entradaquantidade
	, dest.entradavalor
	, 'wget http://localhost/MGLara/estoque/calcula-custo-medio/' || cast(dest.codestoquemes as varchar) as comando
	, 'wget http://localhost/MGLara/estoque/calcula-custo-medio/' || cast(orig.codestoquemes as varchar) as comando
	/*
	*/
from tblestoquemovimento dest
inner join tblestoquemovimento orig on (orig.codestoquemovimento = dest.codestoquemovimentoorigem)
where orig.saidavalor != 0 
and abs(coalesce(orig.saidavalor, 0) - coalesce(dest.entradavalor, 0)) / coalesce(orig.saidavalor, dest.entradavalor) > 0.1
and orig.saidaquantidade = dest.entradaquantidade
--and dest.codestoquemes = 864560
order by 1 desc
--limit 500
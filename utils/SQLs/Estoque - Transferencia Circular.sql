select --distinct
	orig.codestoquemes
	, dest.codestoquemes
	, orig.saidaquantidade
	, dest.entradaquantidade
	, dest.codestoquemovimento
	, orig.codestoquemovimento
	--'wget http://localhost/MGLara/estoque/calcula-custo-medio/' || cast(dest.codestoquemes as varchar) as comando
	--, orig.saidavalor
	--, dest.entradavalor
from tblestoquemovimento dest
inner join tblestoquemovimento orig on (orig.codestoquemovimento = dest.codestoquemovimentoorigem)
where orig.codestoquemes = dest.codestoquemes
and dest.manual
--and dest.codestoquemes = 864560
--and abs(coalesce(orig.saidavalor, 0) - coalesce(dest.entradavalor, 0)) > 0.01
order by 1 desc
--limit 500
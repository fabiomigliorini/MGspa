SET foo.codprodutovariacao = 28420;

delete from tblestoquemovimento 
where codestoquesaldoconferencia in (
	select esc.codestoquesaldoconferencia
	from tblestoquelocalprodutovariacao elpv
	inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
	inner join tblestoquesaldoconferencia esc on (esc.codestoquesaldo = es.codestoquesaldo)
	where elpv.codprodutovariacao = cast(current_setting('foo.codprodutovariacao') as bigint)
	);

delete from tblestoquesaldoconferencia 
where codestoquesaldo in (
	select es.codestoquesaldo
	from tblestoquelocalprodutovariacao elpv
	inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
	where elpv.codprodutovariacao = cast(current_setting('foo.codprodutovariacao') as bigint)
	);

delete from tblestoquemovimento
where codestoquemovimentotipo = 1002
and tblestoquemovimento.codestoquemes in (
	select em.codestoquemes
	from tblestoquelocalprodutovariacao elpv
	inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
	inner join tblestoquemes em on (em.codestoquesaldo = es.codestoquesaldo)
	where elpv.codprodutovariacao = cast(current_setting('foo.codprodutovariacao') as bigint)
	);

select mov.codestoquemes, es.codestoquesaldo
from tblestoquelocalprodutovariacao elpv
inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
inner join tblestoquemes em on (em.codestoquesaldo = es.codestoquesaldo)
inner join tblestoquemovimento mov on (mov.codestoquemes = em.codestoquemes)
where elpv.codprodutovariacao = cast(current_setting('foo.codprodutovariacao') as bigint)
order by mov.codestoquemes desc;

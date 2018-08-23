/*
delete from tblestoquemes
where codestoquemes not in (select distinct mov.codestoquemes from tblestoquemovimento mov where mov.codestoquemes = tblestoquemes.codestoquemes)
and codestoquemes not in (select min(m2.codestoquemes) from tblestoquemes m2 group by m2.codestoquesaldo having count(m2.*) = 1)
;
*/


-- MOVIMENTOS MANUAIS NO ESTOQUE
select distinct mov.codestoquemes, elpv.codestoquelocal, pv.variacao, es.fiscal, em.mes
from tblprodutovariacao pv 
inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao)
inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
inner join tblestoquemes em on (em.codestoquesaldo = es.codestoquesaldo)
inner join tblestoquemovimento mov on (mov.codestoquemes = em.codestoquemes)
where pv.codproduto = 372
--where pv.codprodutovariacao = 372
and mov.manual = true
and es.fiscal = false
--and mov.data >= '2017-01-01'
order by elpv.codestoquelocal, pv.variacao, es.fiscal, em.mes
/*
-- MOVIMENTOS DE CONFERENCIA
select distinct esc.codestoquesaldo
from tblprodutovariacao pv 
inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao)
inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
inner join tblestoquesaldoconferencia esc on (esc.codestoquesaldo = es.codestoquesaldo)
where pv.codproduto = 100790
and es.fiscal = false
*/
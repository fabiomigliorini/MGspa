delete from tblestoquemes
where codestoquemes not in (select distinct mov.codestoquemes from tblestoquemovimento mov where mov.codestoquemes = tblestoquemes.codestoquemes)
and codestoquemes not in (select min(m2.codestoquemes) from tblestoquemes m2 group by m2.codestoquesaldo having count(m2.*) = 1)

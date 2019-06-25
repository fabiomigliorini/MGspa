select ', ' || em.codestoquemovimento::varchar || ', ' || em.codestoquemovimentoorigem::varchar, em.codestoquemes, em.entradaquantidade, em.saidaquantidade --* 
from tblestoquemovimento em
inner join tblestoquemovimento emo on (em.codestoquemovimentoorigem = emo.codestoquemovimento)
where em.codestoquemes = emo.codestoquemes
and em.manual
--limit 100
/*
delete from tblestoquemovimento where manual = true and codestoquemovimento in ( null
, 7762303, 7762302
, 6625256, 6625255
, 6625114, 6625113
, 9525812, 9525811
, 7022144, 7022143
, 8475219, 8475218
, 8236996, 8236995
, 7983748, 7983747
, 7983694, 7983693
, 6354990, 6354989
, 8237112, 8237111
, 7022310, 7022309
, 3622352, 3622351
, 7255229, 7255228
, 3623982, 3623981
, 3624764, 3624763
, 5646856, 5646855
, 6356118, 6356117
)
*/
select codtituloagrupamento, sum(debito), sum(credito) 
from tblmovimentotitulo 
where codtituloagrupamento is not null
and codtipomovimentotitulo in (901, 100, 991, 900)
group by codtituloagrupamento
having sum(debito) != sum(credito) 

--select * from tbltipomovimentotitulo

--delete from tblmovimentotitulo where codtipomovimentotitulo = 991 and codtituloagrupamento = 10652
/*
select * from tblmovimentotitulo where codtituloagrupamento = 30010628

select * from tblmovimentotitulo where codtitulo = 30039324


update tblmovimentotitulo set codtipomovimentotitulo = 100 where codtitulo = 10029424 and codtituloagrupamento = 10004388

update tblmovimentotitulo set codtituloagrupamento = 10005475 where codtitulo = 10034406 and codtituloagrupamento is null and codtipomovimentotitulo = 100

update tbltitulo set codtituloagrupamento = 10005475 where codtitulo = 10034406 


update tblmovimentotitulo set codtituloagrupamento = 10005907 where codtitulo in (30039324
,30039390
,30039400
,30039491
,30039687
,30039732
,30039844
,30039862
,30039906
,30039991
,30040096
,30040084
,30040206
,30040324
,30040385
,30040507
,30040576
,30040624
,30040724
,30040771
,30040577
,10036768
) and sistema = '2013-09-02 12:08:32'

select * from tblmovimentotitulo where codtituloagrupamento is not null and codtipomovimento = 

*/
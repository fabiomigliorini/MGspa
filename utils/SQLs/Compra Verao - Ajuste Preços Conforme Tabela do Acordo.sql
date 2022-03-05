select 'curl https://sistema.mgpapelaria.com.br/MGLara/estoque/gera-movimento-negocio/' || codnegocio, codnaturezaoperacao, codoperacao,  * from tblnegocio n
where n.codpessoa = 12376
and n.codnegociostatus = 2
--and n.codnegocio != 2545203


select count(*) from tblnegocioprodutobarra where valorunitario != (valortotal / quantidade) and quantidade !=0

update tblnegocioprodutobarra set valorunitario = (valortotal / quantidade) where codnegocio in (


update tblnegocioformapagamento 
set valorpagamento = (
	select n.valortotal 
	from tblnegocio n 
	where n.codnegocio = tblnegocioformapagamento.codnegocio
) where tblnegocioformapagamento.codnegocio in (
)


select valorpagamento, * 
from tblnegocioformapagamento t 
where codnegocio = :codnegocio 


select debito, credito, * 
from tbltitulo 
where codnegocioformapagamento in (
	select nfp.codnegocioformapagamento 
	from tblnegocioformapagamento nfp 
	where codnegocio = :codnegocio
)


select t.codtitulo, t.debito, t.credito, nfp.valorpagamento 

select sum(t.debito), sum(t.credito) 
from tbltitulo t
inner join tblnegocioformapagamento nfp on (nfp.codnegocioformapagamento = t.codnegocioformapagamento)
where nfp.codnegocio in (
2537666
,2537665
,2537669
,2537672
,2537683
,2537688
,2537674
,2537773
,2537667
,2541291
,2537706
,2542227
,2537675
,2537681
,2537678
,2537685
,2537235
,2530113
,2530102
,2539400
,2535098
,2537676
,2537668
,2537684
,2539574
,2537686
,2537682
,2537687
,2537673
,2537677
,2536691
,2537671
,2537680
,2537679
,2537633
,2537670
)
--and coalesce(t.debito, 0) + coalesce(t.credito, 0) != coalesce(nfp.valorpagamento, 0)



update tblnegocio set codnegociostatus = 2 where codnegocio = 2544219


select * from tblnegocioformapagamento t where codnegocio = 2544219
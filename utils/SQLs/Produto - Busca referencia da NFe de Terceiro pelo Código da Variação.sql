/*
-- update tblnfeterceiroitem set codprodutobarra = 10010173 where cean = '20070330413980'

 select codprodutobarra, * from tblnfeterceiroitem where cean ilike '7896644703932'
select codprodutobarra, * from tblnfeterceiroitem where cprod ilike '983%' order by xprod

select * from tblnegocioprodutobarra where codprodutobarra = 952785
 
*/
-- PEGA REFERENCIA DA NFE DE TERCEIRO
select 
--	pb.codproduto
--	, pb.codprodutovariacao
--	, 
	cprod
	, xprod
	, cean
	, ceantrib
	, count(*) as qtd
	, min(nt.emissao) as de
	, max(nt.emissao) as ate
	, max(nti.codnfeterceiro) as codnfeterceiro
	, min(vuncom) as de
	, max(vuncom) as ate
from tblnfeterceiroitem nti
inner join tblprodutobarra pb on (pb.codprodutobarra = nti.codprodutobarra)
inner join tblnfeterceiro nt on (nt.codnfeterceiro = nti.codnfeterceiro)
where pb.codproduto = 106109
--where pb.codprodutovariacao between 13276 and 13277
--where pb.codprodutovariacao = 483
group by 
	pb.codproduto
	, pb.codprodutovariacao
	, cprod
	, xprod
	, cean
	, ceantrib
order by 7 desc,  5 desc, 2, 1
--order by 1 desc,  5 desc, 2, 1

--select * from tblnfeterceiroitem where '7897478495222' in (cean, ceantrib)

--select * from tblnfeterceiroitem limit 50


/*
select p.codproduto, p.produto, pv.variacao, p.referencia, pv.referencia, pb.referencia
from tblproduto p
left join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
left join tblprodutobarra pb on (pb.codprodutovariacao = pv.codprodutovariacao)
where p.codmarca = 157
and (coalesce(p.referencia, '') ilike '%402%'
	or coalesce(pv.referencia, '') ilike '%402%'
	or coalesce(pb.referencia, '') ilike '%402%'
	)
*/
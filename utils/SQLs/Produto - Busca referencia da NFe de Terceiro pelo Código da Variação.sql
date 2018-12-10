/*
update tblnfeterceiroitem set codprodutobarra = 30008100 where cean = '9556089002608'
select codprodutobarra, * from tblnfeterceiroitem where ceantrib ilike '%7890000000543%' or cean ilike '%7890000000543%'
select codprodutobarra, * from tblnfeterceiroitem where cprod ilike '0732306' order by criacao desc

select distinct cprod, xprod, cean, ceantrib from tblnfeterceiroitem where xprod ilike '%2032%' and (cean ilike '78944%' or ceantrib ilike '78944%') order by xprod, cean, ceantrib

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
where pb.codproduto = 3705

--where pb.codprodutovariacao between 13276 and 13277
--where pb.codprodutovariacao = 6342
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
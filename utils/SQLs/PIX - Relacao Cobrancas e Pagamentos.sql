-- Forca reconsultar
select 'curl ''https://api.mgspa.mgpapelaria.com.br/api/v1/pix/cob/' || pc.codpixcob || '/consultar'' -X ''POST''', pc.criacao, u.usuario, pc.valororiginal 
 from tblpixcob pc
left join tblpix p on (p.codpixcob = pc.codpixcob)
left join tblnegocio n on (n.codnegocio = pc.codnegocio)
left join tblusuario u on (u.codusuario = n.codusuario)
where pc.codpixcobstatus != 4 -- Expirado 
and p.codpix is null
order by pc.criacao desc

-- PIXCob concluidos
select po.portador, p.horario, p.valor, p.nome, p.txid, n.codnegocio
from tblpixcob t
inner join tblnegocio n on (n.codnegocio = t.codnegocio)
inner join tblusuario u on (u.codusuario = n.codusuario)
inner join tblpix p on (p.codpixcob = t.codpixcob)
inner join tblportador po on (po.codportador = p.codportador)
where t.criacao >= '2021-10-13'
order by p.horario desc, po.portador asc,  p.valor, p.codpix 

-- Totais de PIX Movimentado
select 
	po.portador, 
	pcs.pixcobstatus,
	count(t.codpixcob) as quantidade, 
	sum(t.valororiginal) as valor, 
	sum(t.valororiginal) / count(t.codpixcob) as media, 
	count(p.codpix) as quantidade, 
	sum(p.valor) as valor, 
	sum(p.valor) / count(p.codpix) as media, 
	min(p.criacao) as de, 
	max(p.criacao) as ate
from tblpixcob t
inner join tblnegocio n on (n.codnegocio = t.codnegocio)
inner join tblusuario u on (u.codusuario = n.codusuario)
left join tblpix p on (p.codpixcob = t.codpixcob)
left join tblportador po on (po.codportador = t.codportador)
inner join tblpixcobstatus pcs on (pcs.codpixcobstatus = t.codpixcobstatus)
group by po.portador, pcs.pixcobstatus, t.codpixcobstatus 
order by t.codpixcobstatus, 2 DESC

-- Marca PIXCOb com mais de um dia EXPIRADOS
update tblpixcob 
set codpixcobstatus = 4
where codpixcobstatus in (1, 3)
and criacao + (expiracao || ' second')::interval < now() - '1 day'::interval


select t.valororiginal, pcs.pixcobstatus, t.criacao, t.solicitacaopagador 
from tblpixcob t
inner join tblpixcobstatus pcs on (pcs.codpixcobstatus = t.codpixcobstatus)
order by t.criacao desc
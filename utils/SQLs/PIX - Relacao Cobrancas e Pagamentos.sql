-- Forca reconsultar
select 'curl ''https://api.mgspa.mgpapelaria.com.br/api/v1/pix/cob/' || pc.codpixcob || '/consultar'' -X ''POST''', pc.criacao
 from tblpixcob pc
left join tblpix p on (p.codpixcob = pc.codpixcob)
where p.codpix is null
order by pc.criacao desc

-- PIXCob concluidos
select po.portador, p.horario, p.valor, p.nome, p.txid, n.codnegocio
from tblpixcob t
inner join tblnegocio n on (n.codnegocio = t.codnegocio)
inner join tblusuario u on (u.codusuario = n.codusuario)
inner join tblpix p on (p.codpixcob = t.codpixcob)
inner join tblportador po on (po.codportador = p.codportador)
where t.criacao >= '2021-10-13'
order by po.portador asc, p.horario asc , p.valor, p.codpix 


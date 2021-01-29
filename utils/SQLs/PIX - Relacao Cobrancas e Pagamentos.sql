select u.usuario, n.codnegocio, t.criacao, t.txid, t.valororiginal, p.e2eid, p.valor as valorpago
from tblpixcob t
inner join tblnegocio n on (n.codnegocio = t.codnegocio)
inner join tblusuario u on (u.codusuario = n.codusuario)
left join tblpix p on (p.codpixcob = t.codpixcob)
where t.criacao >= '2021-01-28'
order by t.criacao asc

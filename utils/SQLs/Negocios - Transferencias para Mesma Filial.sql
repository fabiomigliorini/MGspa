/*
select mov.codestoquemes, mov.entradaquantidade, mov.entradavalor, mov.manual, mov.codnegocioprodutobarra, npb.codnegocio, n.codfilial
from tblestoquemovimento mov
inner join tblestoquemovimento orig on (orig.codestoquemovimento = mov.codestoquemovimentoorigem)
left join tblnegocioprodutobarra npb on (npb.codnegocioprodutobarra = mov.codnegocioprodutobarra)
left join tblnegocio n on (n.codnegocio = npb.codnegocio)
where mov.codestoquemes = orig.codestoquemes
and mov.codestoquemovimentotipo in (4101, 4201)
--limit 50
*/
--select * from tblestoquemovimentotipo

select u.usuario, date_trunc('month', n.lancamento), n.codnegocio, f.filial
from tblnegocio n
inner join tblfilial f on (f.codfilial = n.codfilial)
left join tblusuario u on (u.codusuario = n.codusuario)
where n.codpessoa = f.codpessoa
and n.codnaturezaoperacao in (15, 16)
and n.codnegociostatus = 2
order by n.lancamento desc

update tblnegocio set codnaturezaoperacao  = 18 where codnegocio = 2019849





select p.fantasia, f.filial, t.numero, t.saldo, t.debito, t.credito, t.vencimento, t.criacao, t.codtitulo 
from tbltitulo t
inner join tblpessoa p on (p.codpessoa = t.codpessoa)
inner join tblfilial f on (f.codfilial = t.codfilial)
where t.DEBITO = 599.5
--where t.debito between 10300 and 10400
--where t.credito = 369.98
order by criacao desc nulls LAST

select * 
from tblliquidacaotitulo lt
where lt.observacao ilike '%Jaiclea%'

select * from tbltitulo where observacao ilike '%5419%'

select * from tblnegocio t where valortotal = 8 order by lancamento desc

select * from tblnotafiscal t2  where valortotal = 369.98

select vprod, complemento, vprod * 2, * from tblnfeterceiroitem where codnfeterceiro = 32388 order by nitem

select * from tblmovimentotitulo t where codtitulo = 312495 order by criacao desc

delete from tblmovimentotitulo where codmovimentotitulo = 700747

update tblmovimentotitulo set codtitulo = 312495 where codmovimentotitulo in (700747, 700748)

update tblmovimentotitulo set debito  = debito where codtitulo = 312495

update tbltitulo set numero = '202100000000026', gerencial = false, fatura = '202100000000026' where codtitulo = 359081

select * from tblnegocio where valortotal = 141.3 order by criacao desc

select * from tblliquidacaotitulo where credito = 69.75 order by criacao desc

select * from tblferiado t order by data

update tblnegocio set codnegociostatus = 2 where codnegocio = 2232235


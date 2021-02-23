select p.fantasia, f.filial, t.numero, t.saldo, t.debito, t.credito, t.vencimento, t.criacao
from tbltitulo t
inner join tblpessoa p on (p.codpessoa = t.codpessoa)
inner join tblfilial f on (f.codfilial = t.codfilial)
where t.debito = 369.98
--where t.debito between 10300 and 10400
--where t.credito = 369.98
order by criacao desc nulls LAST

select * 
from tblliquidacaotitulo lt
where lt.observacao ilike '%RM%bio%'

select * from tbltitulo where observacao ilike '%5419%'

select * from tblnegocio t where valortotal = 369.98

select * from tblnotafiscal t2  where valortotal = 369.98

select vprod, complemento, vprod * 2, * from tblnfeterceiroitem where codnfeterceiro = 32388 order by nitem



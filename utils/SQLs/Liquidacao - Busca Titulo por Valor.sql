select p.fantasia, f.filial, t.numero, t.saldo, t.debito, t.credito, t.vencimento, t.criacao
from tbltitulo t
inner join tblpessoa p on (p.codpessoa = t.codpessoa)
inner join tblfilial f on (f.codfilial = t.codfilial)
--where t.debito = 179.9
--where t.debito between 10300 and 10400
where t.credito = 3300
order by criacao desc nulls LAST

select * 
from tblliquidacaotitulo lt
where lt.observacao ilike '%CELSO%NUNES%'

select * from tbltitulo where observacao ilike '%5419%'
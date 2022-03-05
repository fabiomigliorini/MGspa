select p.fantasia, f.filial, t.numero, t.saldo, t.debito, t.credito, t.vencimento, t.criacao, t.codtitulo 
from tbltitulo t
inner join tblpessoa p on (p.codpessoa = t.codpessoa)
inner join tblfilial f on (f.codfilial = t.codfilial)
--where t.debito = 89
where t.debito = 541.94
--where t.credito = 89
--and saldo > 0
order by criacao desc nulls LAST

select p.fantasia, f.filial, t.numero, t.saldo, t.debito, t.credito, t.vencimento, t.criacao, t.codtitulo 
from tbltitulo t
inner join tblpessoa p on (p.codpessoa = t.codpessoa)
inner join tblfilial f on (f.codfilial = t.codfilial)
where t.observacao ilike '%poço%'
order by criacao desc nulls LAST


select valortotal, valoraprazo, * 
from tblnegocio n 
where n.valortotal = 219.3
and criacao >= '2021-07-22'
order by criacao desc

select criacao, * from tblpixcob where valororiginal = 20

update tblnegocio set codnegociostatus  =   1 where codnegocio = 2350905

select * 
from tblliquidacaotitulo lt
where lt.observacao ilike '%FELIPPE%'

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

select distinct codpessoa from tblnegocio t where lancamento >= '2020-01-01' 

select 'wget https://api.mgspa.mgpapelaria.com.br/api/v1/stone-connect/pre-transacao/' || cast(spt.codstonepretransacao  as varchar), spt.criacao, st.codstonetransacao, spt.status 
from tblstonepretransacao spt
left join tblstonetransacao st on (st.codstonepretransacao = spt.codstonepretransacao)
where st.codstonetransacao is null
order by spt.criacao desc

update tblnotafiscal set nfeautorizacao = null, nfedataautorizacao =null where codnotafiscal = 1959590


update tblnegocio set codnegociostatus = 1 where codnegocio = 2467310
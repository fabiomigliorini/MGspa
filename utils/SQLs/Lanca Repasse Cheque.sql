select * from tblportador

insert into tblchequerepasse (codportador, data, observacoes, criacao, codusuariocriacao)
values (210, '2017-08-21', null, '2017-08-21 11:41', 1)

select * from tblchequerepasse order by codchequerepasse desc 

insert into tblchequerepassecheque (codcheque, codchequerepasse, criacao, codusuariocriacao)
select codcheque, 2085, '2017-08-21 11:41', 1
from tblcheque where cmc7 in (
'<10408547<0189000525>000300018892:',
'<00159111<0188501395>142004587160:',
'<74808185<0180017965>900002358737:',
'<34113641<0480010675>701891993964:',
'<74880161<0480001765>200007014244:',
'<00142701<0188505275>355004378836:',
'<00182330<0488500355>734000118603:'
)

update tblcheque set indstatus = 2 where indstatus = 1 and codcheque in (select crc.codcheque from tblchequerepassecheque crc where codchequerepasse = 2085)

select crc.codchequerepasse, sum(c.valor), count(crc.codchequerepassecheque)
from tblchequerepassecheque crc
inner join tblcheque c on (c.codcheque = crc.codcheque)
where crc.codchequerepasse >= 1900
group by crc.codchequerepasse
order by 1 desc

update tblchequerepasse set data = '2017-08-21', criacao = '2017-08-21 11:41' where codchequerepasse = 2070
update tblchequerepassecheque set criacao = '2017-08-21 11:41' where codchequerepasse = 2070

select * from tblchequerepassecheque where codchequerepasse = 2057

delete from tblchequerepassecheque where codchequerepassecheque between 15837 and 15847

update tblchequerepassecheque set codchequerepasse = 2049 where codchequerepassecheque = 15793
delete from tblchequerepassecheque where codchequerepassecheque in (15693, 15692)

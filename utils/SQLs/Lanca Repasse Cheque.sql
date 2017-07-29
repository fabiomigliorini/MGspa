select * from tblportador

insert into tblchequerepasse (codportador, data, observacoes, criacao, codusuariocriacao)
values (210, '2017-07-28', null, '2017-07-28 11:41', 1)

select * from tblchequerepasse order by codchequerepasse desc 

insert into tblchequerepassecheque (codcheque, codchequerepasse, criacao, codusuariocriacao)
select codcheque, 2072, '2017-07-28 11:41', 1
from tblcheque where cmc7 in (
'<74880748<0180000435>100003474772:',
'<23715825<0180004425>415650015280:',
'<34182182<0480000685>871591656302:'
)

update tblcheque set indstatus = 2 where indstatus = 1 and codcheque in (select crc.codcheque from tblchequerepassecheque crc where codchequerepasse = 2072)

select crc.codchequerepasse, sum(c.valor), count(crc.codchequerepassecheque)
from tblchequerepassecheque crc
inner join tblcheque c on (c.codcheque = crc.codcheque)
where crc.codchequerepasse >= 1900
group by crc.codchequerepasse
order by 1 desc

update tblchequerepasse set data = '2017-07-28', criacao = '2017-07-28 11:41' where codchequerepasse = 2070
update tblchequerepassecheque set criacao = '2017-07-28 11:41' where codchequerepasse = 2070

select * from tblchequerepassecheque where codchequerepasse = 2057

delete from tblchequerepassecheque where codchequerepassecheque between 15837 and 15847

update tblchequerepassecheque set codchequerepasse = 2049 where codchequerepassecheque = 15793
delete from tblchequerepassecheque where codchequerepassecheque in (15693, 15692)

select * from tblportador

insert into tblchequerepasse (codportador, data, observacoes, criacao, codusuariocriacao)
values (210, '2017-08-24', null, '2017-08-24 11:41', 1)

select * from tblchequerepasse order by codchequerepasse desc 

insert into tblchequerepassecheque (codcheque, codchequerepasse, criacao, codusuariocriacao)
select codcheque, 2088, '2017-08-24 11:41', 1
from tblcheque where cmc7 in (
'<00142700<0188501635>366005740072:',
'<74880038<0180001265>000007495914:',
'<00142702<0188521185>349003851901:',
'<74881012<0180004295>200009840513:',
'<10408549<0189000435>000300018892:'
)

update tblcheque set indstatus = 2 where indstatus = 1 and codcheque in (select crc.codcheque from tblchequerepassecheque crc where codchequerepasse = 2088)

select crc.codchequerepasse, sum(c.valor), count(crc.codchequerepassecheque)
from tblchequerepassecheque crc
inner join tblcheque c on (c.codcheque = crc.codcheque)
where crc.codchequerepasse >= 1900
group by crc.codchequerepasse
order by 1 desc

update tblchequerepasse set data = '2017-08-24', criacao = '2017-08-24 11:41' where codchequerepasse = 2070
update tblchequerepassecheque set criacao = '2017-08-24 11:41' where codchequerepasse = 2070

select * from tblchequerepassecheque where codchequerepasse = 2086

delete from tblchequerepassecheque where codchequerepassecheque between 15837 and 15847

update tblchequerepassecheque set codchequerepasse = 2049 where codchequerepassecheque = 15793
delete from tblchequerepassecheque where codchequerepassecheque in (15693, 15692)

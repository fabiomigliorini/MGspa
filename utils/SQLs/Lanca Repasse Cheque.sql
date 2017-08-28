select * from tblportador

insert into tblchequerepasse (codportador, data, observacoes, criacao, codusuariocriacao)
values (210, '2017-08-25', null, '2017-08-25 11:41', 1)

select * from tblchequerepasse order by codchequerepasse desc 

insert into tblchequerepassecheque (codcheque, codchequerepasse, criacao, codusuariocriacao)
select codcheque, 2089, '2017-08-25 11:41', 1
from tblcheque where cmc7 in (
'<34113645<0480001155>791591238596:',
'<00141377<0188501335>448000663541:',
'<00141378<0188501325>423000663544:',
'<23713808<0180047975>333001879338:',
'<34182181<0480002265>871250026433:',
'<34182187<0480001055>891301702428:'
)

update tblcheque set indstatus = 2 where indstatus = 1 and codcheque in (select crc.codcheque from tblchequerepassecheque crc where codchequerepasse = 2089)

select crc.codchequerepasse, sum(c.valor), count(crc.codchequerepassecheque)
from tblchequerepassecheque crc
inner join tblcheque c on (c.codcheque = crc.codcheque)
where crc.codchequerepasse >= 1900
group by crc.codchequerepasse
order by 1 desc

update tblchequerepasse set data = '2017-08-25', criacao = '2017-08-25 11:41' where codchequerepasse = 2070
update tblchequerepassecheque set criacao = '2017-08-25 11:41' where codchequerepasse = 2070

select * from tblchequerepassecheque where codchequerepasse = 2086

delete from tblchequerepassecheque where codchequerepassecheque between 15837 and 15847

update tblchequerepassecheque set codchequerepasse = 2049 where codchequerepassecheque = 15793
delete from tblchequerepassecheque where codchequerepassecheque in (15693, 15692)

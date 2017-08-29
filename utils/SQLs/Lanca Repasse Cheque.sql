select * from tblportador

insert into tblchequerepasse (codportador, data, observacoes, criacao, codusuariocriacao)
values (210, '2017-08-28', null, '2017-08-28 11:41', 1)

select * from tblchequerepasse order by codchequerepasse desc 

insert into tblchequerepassecheque (codcheque, codchequerepasse, criacao, codusuariocriacao)
select codcheque, 2090, '2017-08-28 11:41', 1
from tblcheque where cmc7 in (
'<00142704<0188503185>327004379056:',
'<23702342<0180012945>314600478805:',
'<00142708<0188521205>388003851909:',
'<00142705<0188500105>395004521225:',
'<00142702<0488500175>320001918687:'
)

update tblcheque set indstatus = 2 where indstatus = 1 and codcheque in (select crc.codcheque from tblchequerepassecheque crc where codchequerepasse = 2090)

select crc.codchequerepasse, sum(c.valor), count(crc.codchequerepassecheque)
from tblchequerepassecheque crc
inner join tblcheque c on (c.codcheque = crc.codcheque)
where crc.codchequerepasse >= 1900
group by crc.codchequerepasse
order by 1 desc

update tblchequerepasse set data = '2017-08-28', criacao = '2017-08-28 11:41' where codchequerepasse = 2070
update tblchequerepassecheque set criacao = '2017-08-28 11:41' where codchequerepasse = 2070

select * from tblchequerepassecheque where codchequerepasse = 2086

delete from tblchequerepassecheque where codchequerepassecheque between 15837 and 15847

update tblchequerepassecheque set codchequerepasse = 2049 where codchequerepassecheque = 15793
delete from tblchequerepassecheque where codchequerepassecheque in (15693, 15692)

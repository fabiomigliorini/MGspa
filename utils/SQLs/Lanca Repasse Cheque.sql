select * from tblportador

insert into tblchequerepasse (codportador, data, observacoes, criacao, codusuariocriacao)
values (210, '2018-06-20', null, '2018-06-20 11:41', 1)

select * from tblchequerepasse order by codchequerepasse desc 

insert into tblchequerepassecheque (codcheque, codchequerepasse, criacao, codusuariocriacao)
select codcheque, 2211, '2018-06-20 11:41', 1
from tblcheque where cmc7 in (
'<00142700<0188502625>306001442075:',
'<00142707<0188503725>308004392315:',
'<00142700<0188503205>303004379305:',
'<00142705<0188503255>363004851521:',
'<00142703<0188507315>325002702105:',
'<00142704<0188503265>379004846014:',
'<00142703<0188504755>314002699057:',
'<00142708<0188508195>364003239858:'
)

update tblcheque set indstatus = 2 where indstatus = 1 and codcheque in (select crc.codcheque from tblchequerepassecheque crc)

select crc.codchequerepasse, sum(c.valor), count(crc.codchequerepassecheque)
from tblchequerepassecheque crc
inner join tblcheque c on (c.codcheque = crc.codcheque)
where crc.codchequerepasse >= 1900
group by crc.codchequerepasse
order by 1 desc

update tblcheque set valor = 172.32 where cmc7 = '<34113644<0480001325>711720814794:'

update tblchequerepasse set data = '2018-06-20', criacao = '2018-06-20 11:41' where codchequerepasse = 2187
update tblchequerepassecheque set criacao = '2018-06-20 11:41' where codchequerepasse = 2187

select * from tblchequerepassecheque where codchequerepasse = 2086

delete from tblchequerepassecheque where codchequerepassecheque between 15837 and 15847

update tblchequerepassecheque set codchequerepasse = 2187 where codchequerepassecheque in (16531
,16530
,16529
,16528
,16527
,16526
,16525
,16524
,16523
,16522
,16521
,16520
,16519
,16518
)
delete from tblchequerepassecheque where codchequerepassecheque in (15693, 15692)


/*
-- INSERE CHEQUE COMO DEVOLVIDO
insert into tblchequedevolucao (codchequerepassecheque, codchequemotivodevolucao, data, observacoes, criacao, alteracao, codusuariocriacao, codusuarioalteracao)
select crc.codchequerepassecheque, cmd.codchequemotivodevolucao, '2017-08-15', 'Repassado para cobranca', '2017-08-31', '2017-08-31', 1, 1
from tblchequerepassecheque crc
inner join tblcheque c on (c.codcheque = crc.codcheque)
inner join tblchequemotivodevolucao cmd on (cmd.numero = 12)
where c.cmc7 = '<34182182<0480000685>871591656302:'

-- MARCA INDSTATUS - EM COBRANCA
update tblcheque set indstatus = 4 where indstatus = 2 and codcheque in (select codcheque from tblcheque where cmc7 = '<34182182<0480000685>871591656302:')


*/

select * 
from tblcheque c
inner join tblchequerepassecheque crc  on (crc.codcheque = c.codcheque)
where crc.codchequerepasse = 2186


select cr.*
from tblchequerepassecheque crc
inner join tblchequerepasse cr on (cr.codchequerepasse = crc.codchequerepasse)
where crc.codcheque = 8836


update tblchequerepassecheque set codchequerepasse = 2155
where codcheque in(select codcheque from tblcheque where cmc7 in (
'<03341685<0180000205>219130149255:',
'<74880161<0180001075>200000901660:',
'<00138632<0188509225>674000570023:'
))

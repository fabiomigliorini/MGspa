select * from tblportador

insert into tblchequerepasse (codportador, data, observacoes, criacao, codusuariocriacao)
values (210, '2018-01-31', null, '2018-01-31 11:41', 1)

select * from tblchequerepasse order by codchequerepasse desc 

insert into tblchequerepassecheque (codcheque, codchequerepasse, criacao, codusuariocriacao)
select codcheque, 2157, '2018-01-31 11:41', 1
from tblcheque where cmc7 in (
'<00111802<0188500135>744012289862:',
'<10408548<0189000445>000111150595:'
)

update tblcheque set indstatus = 2 where indstatus = 1 and codcheque in (select crc.codcheque from tblchequerepassecheque crc where codchequerepasse = 2157)

select crc.codchequerepasse, sum(c.valor), count(crc.codchequerepassecheque)
from tblchequerepassecheque crc
inner join tblcheque c on (c.codcheque = crc.codcheque)
where crc.codchequerepasse >= 1900
group by crc.codchequerepasse
order by 1 desc

update tblchequerepasse set data = '2018-01-31', criacao = '2018-01-31 11:41' where codchequerepasse = 2149
update tblchequerepassecheque set criacao = '2018-01-31 11:41' where codchequerepasse = 2100

select * from tblchequerepassecheque where codchequerepasse = 2086

delete from tblchequerepassecheque where codchequerepassecheque between 15837 and 15847

update tblchequerepassecheque set codchequerepasse = 2049 where codchequerepassecheque = 15793
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
where crc.codchequerepasse = 2140


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

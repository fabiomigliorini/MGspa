select * from tblportador order by codportador

insert into tblchequerepasse (codportador, data, observacoes, criacao, codusuariocriacao)
values (210, '2019-09-30', null, '2019-09-30 11:41', 1)

select * from tblchequerepasse order by codchequerepasse desc limit 50 

insert into tblchequerepassecheque (codcheque, codchequerepasse, criacao, codusuariocriacao)
select codcheque, 2400, '2019-09-30 10:00', 1
from tblcheque where cmc7 in (
	'<00142704<0188518035>357010510675:',
	'<00117791<0188509495>686000624642:',
	'<10408541<0189009675>000300245602:',
	'<74880038<0180001005>000000641555:',
	'<23702341<0180009255>324005472204:'
)

update tblcheque set indstatus = 2 where indstatus = 1 and codcheque in (select crc.codcheque from tblchequerepassecheque crc)

select crc.codchequerepasse, sum(c.valor), count(crc.codchequerepassecheque)
from tblchequerepassecheque crc
inner join tblcheque c on (c.codcheque = crc.codcheque)
where crc.codchequerepasse >= 1900
group by crc.codchequerepasse
order by 1 desc

--update tblcheque set valor = 323.97 where cmc7 = '<00141374<0188503675>497000594552:'

--update tblchequerepasse set data = '2019-09-30', criacao = '2019-04-08 11:41' where codchequerepasse = 2336
--update tblchequerepassecheque set criacao = '2019-09-30 11:41' where codchequerepasse = 2286

--select * from tblchequerepassecheque where codchequerepasse = 2398

--update tblchequerepassecheque set codchequerepasse = 2399 where codchequerepassecheque in (17214, 17213)

--delete from tblchequerepassecheque where codchequerepassecheque between 15837 and 15847

/*
delete from tblchequerepassecheque  where codcheque in (
	select codcheque
	from tblcheque where cmc7 in (
	'<23755812<0180001305>282200703864:',
	'<23755813<0180000715>241300343476:'
	)
)

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

*/
/*
-- INSERE CHEQUE COMO DEVOLVIDO
insert into tblchequedevolucao (codchequerepassecheque, codchequemotivodevolucao, data, observacoes, criacao, alteracao, codusuariocriacao, codusuarioalteracao)
select crc.codchequerepassecheque, cmd.codchequemotivodevolucao, '2017-08-15', 'Repassado para cobranca', '2017-08-31', '2017-08-31', 1, 1
from tblchequerepassecheque crc
inner join tblcheque c on (c.codcheque = crc.codcheque)
inner join tblchequemotivodevolucao cmd on (cmd.numero = 12)
where c.cmc7 = '<34182182<0480001185>801421544691:'

-- MARCA INDSTATUS - EM COBRANCA
update tblcheque set indstatus = 4 where indstatus = 2 and codcheque in (select codcheque from tblcheque where cmc7 = '<03341682<0180003045>275130143037:')


select * from tblchequedevolucao order by codchequedevolucao desc

update tblchequedevolucao set data = '2018-08-30', criacao = date_trunc('second', now()), alteracao = date_trunc('second', now())where codchequedevolucao = 312

*/

select * 
from tblcheque c
inner join tblchequerepassecheque crc  on (crc.codcheque = c.codcheque)
where crc.codchequerepasse = 2186


select cr.*
from tblchequerepassecheque crc
inner join tblchequerepasse cr on (cr.codchequerepasse = crc.codchequerepasse)
where crc.codcheque = 8836


update tblchequerepassecheque set codchequerepasse = 2295
where codcheque in(select codcheque from tblcheque where cmc7 in (
'<23755815<0180000535>272500343479:',
'<10432634<0189000635>100300031358:'
))

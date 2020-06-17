--select * from tblportador order by codportador

-- Cria Repasse
insert into tblchequerepasse (codportador, data, observacoes, criacao, codusuariocriacao)
values (210, '2020-05-19', null, '2020-05-19 11:41', 1);
select * from tblchequerepasse order by codchequerepasse desc limit 50

-- Vincula Cheques ao repasse
insert into tblchequerepassecheque (codcheque, codchequerepasse, criacao, codusuariocriacao)
select codcheque, 2467, '2020-05-19 10:00', 1
from tblcheque where cmc7 in (
	'<00111805<0188511255>747010647973:',
	'NIL'
	);

-- Marca Cheque como repassado
update tblcheque set indstatus = 2 where indstatus = 1 and codcheque in (select crc.codcheque from tblchequerepassecheque crc);

-- Consulta Total Cheques do Repasse
select crc.codchequerepasse, sum(c.valor), count(crc.codchequerepassecheque)
from tblchequerepassecheque crc
inner join tblcheque c on (c.codcheque = crc.codcheque)
where crc.codchequerepasse >= 1900
group by crc.codchequerepasse
order by 1 desc;

--update tblcheque set valor = 390 where cmc7 = '<74880036<0180040885>000007622962:'

--update tblchequerepasse set data = '2020-05-19', criacao = '2019-04-08 11:41' where codchequerepasse = 2336
--update tblchequerepassecheque set criacao = '2020-05-19 11:41' where codchequerepasse = 2286

--select * from tblchequerepassecheque where codchequerepasse = 2398

-- update tblcheque set indstatus = 1 where codcheque = 10135

--update tblchequerepassecheque set codchequerepasse = 2399 where codchequerepassecheque in (17214, 17213)

--delete from tblchequerepassecheque where codchequerepassecheque between 15837 and 15847

/*
delete from tblchequerepassecheque  where codchequerepasse = 2463 and codcheque in (
	select codcheque
	from tblcheque where cmc7 in (
	'<34113645<0480002065>701200814794:'
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
select crc.codchequerepassecheque, cmd.codchequemotivodevolucao, '2019-12-20', 'Repassado para cobranca', '2019-12-20', '2019-12-20', 1, 1
from tblchequerepassecheque crc
inner join tblcheque c on (c.codcheque = crc.codcheque)
inner join tblchequemotivodevolucao cmd on (cmd.numero = 22)
where c.cmc7 = '<00159111<0188500065>109000852518:'

-- MARCA INDSTATUS - EM COBRANCA
update tblcheque set indstatus = 4 where indstatus = 2 and codcheque in (select codcheque from tblcheque where cmc7 = '<00159111<0188500065>109000852518:')

update tblcheque set indstatus = 5 where indstatus = 1 and codcheque in (9940)



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

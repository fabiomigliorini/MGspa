
insert into tblchequedevolucao (codchequerepassecheque, codchequemotivodevolucao, data, observacoes)
select 
	(select codchequerepassecheque from tblchequerepassecheque where codcheque = 8906) as codchequerepassecheque,
	(select codchequemotivodevolucao from tblchequemotivodevolucao where numero = 11) as codchequemotivodevolucao,
	'2017-12-15' as data,
	'Depositou em 18/12/2017 na conta 22954-7'

update tblcheque set indstatus = 3 where codcheque = 8906


select c.valor, c.indstatus, cd.*
from tblcheque c 
inner join tblchequerepassecheque crc on (crc.codcheque = c.codcheque)
inner join tblchequedevolucao cd on (cd.codchequerepassecheque = crc.codchequerepassecheque)
where c.codcheque = 8906


select * from tblchequemotivodevolucao where numero = 21
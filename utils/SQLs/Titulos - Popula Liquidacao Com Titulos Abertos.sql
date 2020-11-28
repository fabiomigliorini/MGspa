-- select * from tblmovimentotitulo m where m.codliquidacaotitulo = 70807
/*
insert into tblmovimentotitulo (
	codtipomovimentotitulo, 
	codtitulo, 
	codportador, 
	debito, 
	credito, 
	transacao, 
	sistema, 
	codliquidacaotitulo, 
	alteracao, 
	codusuarioalteracao, 
	criacao, 
	codusuariocriacao)
select 
	600 as codtipomovimentotitulo,
	t.codtitulo ,
	100 as codportador ,
	t.creditosaldo  as debito,
	t.debitosaldo as credito ,
	'2020-11-24' as transacao ,
	'2020-11-24 17:40:11' as sistema ,
	70811 as codliquidacaotitulo,
	'2020-11-24 17:40:11' as alteracao ,
	1 as codusuarioalteracao ,
	'2020-11-24 17:40:11' as criacao,
	1 as codusuariocriacao 
from tbltitulo t 
where t.codpessoa = 2677
and t.saldo != 0
and t.emissao <= '2020-10-31'
*/
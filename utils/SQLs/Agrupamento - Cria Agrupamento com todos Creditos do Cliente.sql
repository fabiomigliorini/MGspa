with creditos as (
	select * 
	from tbltitulo t 
	where t.saldo < 0 
	and t.codpessoa = :codpessoa
	--and t.codtipotitulo = 3
)
INSERT INTO 
	tbltituloagrupamento (
		emissao, 
		cancelamento, 
		observacao, 
		alteracao, 
		codusuarioalteracao, 
		criacao, 
		codusuariocriacao, 
		debito, 
		credito, 
		codpessoa
	)
select 
		date_trunc('second', now()) as emissao, 
		null as cancelamento, 
		'Agrupamento Creditos' as observacao, 
		date_trunc('second', now()) as alteracao, 
		null as codusuarioalteracao, 
		date_trunc('second', now())  as criacao, 
		null as codusuariocriacao, 
		null as debito, 
		sum(saldo) * -1 as credito, 
		min(codpessoa) as codpessoa
from creditos c


INSERT INTO tbltitulo (
	codtipotitulo, 
	codfilial, 
	codportador, 
	codpessoa, 
	codcontacontabil, 
	numero, 
	fatura, 
	transacao, 
	sistema, 
	emissao, 
	vencimento, 
	vencimentooriginal, 
	debito, 
	credito, 
	gerencial, 
	observacao, 
	boleto, 
	nossonumero, 
	debitototal, 
	creditototal, 
	saldo, 
	debitosaldo, 
	creditosaldo, 
	transacaoliquidacao, 
	codnegocioformapagamento, 
	codtituloagrupamento, 
	remessa, 
	estornado, 
	alteracao, 
	codusuarioalteracao, 
	criacao, 
	codusuariocriacao, 
	codvalecompraformapagamento
)
select 
	911 as codtipotitulo, --Agrupamento Credito
	:codfilial as codfilial, -- Deposito
	null as codportador, 
	codpessoa, 
	7 as codcontacontabil, -- Agrupamento
	:numero as numero, 
	null as fatura, 
	date_trunc('second', now()) as transacao, 
	date_trunc('second', now()) as sistema, 
	date_trunc('second', now()) as emissao, 
	date_trunc('second', now() + '2 years') as vencimento, 
	date_trunc('second', now() + '2 years') as vencimentooriginal, 
	debito, 
	credito, 
	true as gerencial, 
	null as observacao, 
	false as boleto, 
	null as nossonumero, 
	debito as debitototal, 
	credito as creditototal, 
	debito - credito as saldo, 
	debito as debitosaldo, 
	credito as creditosaldo, 
	null as transacaoliquidacao, 
	null as codnegocioformapagamento, 
	codtituloagrupamento, 
	null as remessa, 
	null as estornado, 
	alteracao, 
	codusuarioalteracao, 
	criacao, 
	codusuariocriacao, 
	null as codvalecompraformapagamento
from tbltituloagrupamento ta 
where ta.codtituloagrupamento = :codtituloagrupamento 



with creditos as (
	select * 
	from tbltitulo t 
	where t.saldo < 0 
	and t.codpessoa = :codpessoa 
	--and t.codtipotitulo = 3
)
INSERT INTO mgsis.tblmovimentotitulo (
	codtipomovimentotitulo, 
	codtitulo, 
	codportador, 
	codtitulorelacionado, 
	debito, 
	credito, 
	historico, 
	transacao, 
	sistema, 
	codliquidacaotitulo, 
	codtituloagrupamento, 
	codboletoretorno, 
	codcobranca, 
	alteracao, 
	codusuarioalteracao, 
	criacao, 
	codusuariocriacao, 
	codtituloboleto) 
select 
	901 as codtipomovimentotitulo, 
	codtitulo, 
	codportador, 
	null as codtitulorelacionado, 
	c.creditosaldo as debito, 
	c.debitosaldo as credito, 
	null as historico, 
	ta.criacao as transacao, 
	ta.criacao as sistema, 
	null as codliquidacaotitulo, 
	ta.codtituloagrupamento as codtituloagrupamento, 
	null as codboletoretorno, 
	null as codcobranca, 
	ta.alteracao, 
	ta.codusuarioalteracao, 
	ta.criacao, 
	ta.codusuariocriacao, 
	null as codtituloboleto
from creditos c
inner join tbltituloagrupamento ta on (ta.codtituloagrupamento = :codtituloagrupamento)
where coalesce(c.codtituloagrupamento, 0) != ta.codtituloagrupamento


select sum(credito), sum(debito) from tblmovimentotitulo t where t.codtituloagrupamento = 26507


--select * from tblmovimentotitulo t where codtitulo = 335842 order by criacao desc


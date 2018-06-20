-- BUSCA MOVIMENTOS COM DATA ERRADA
select * from tblestoquesaldoconferencia where data > criacao

-- ALTERA PRA DATA DE CRIACAO
update tblestoquesaldoconferencia 
set data = criacao 
where data > criacao
and codestoquesaldoconferencia in (
100932, 
100933
)

-- MANDAR RECALCULAR MOVIMENTO PRA CADA REGISTRO ALTERADO
--wget http://sistema.mgpapelaria.com.br/MGLara/estoque/gera-movimento-conferencia/100932
--wget http://sistema.mgpapelaria.com.br/MGLara/estoque/gera-movimento-conferencia/100933

-- APAGA MESES DESNECESSARIOS
delete from tblestoquemes 
where codestoquemes not in (
	select distinct mov.codestoquemes 
	from tblestoquemovimento mov
	)
and codestoquesaldo not in (
	select m2.codestoquesaldo
	from tblestoquemes m2 
	group by m2.codestoquesaldo 
	having count(m2.codestoquemes) = 1
	)

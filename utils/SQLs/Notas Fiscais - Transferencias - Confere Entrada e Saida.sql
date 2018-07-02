/*
select * from tblestoquemovimento where date_trunc('day', data) >= '2018-07-01' and manual
select count(*) from tblestoquemovimento where data = '2018-06-30 23:59:59' and manual

--delete from tblestoquemovimento where date_trunc('day', data) = '2018-07-01' and manual


select saida.total_saida, entrada.total_entrada, saida.total_saida - entrada.total_entrada as diferenca from saida, entrada

select * from tblnaturezaoperacao where naturezaoperacao ilike '%transfer%'

-- ajusta data de emissao atrasada
update tblnotafiscal 
set emissao = '2018-06-30 16:30:00'
, saida = '2018-06-30 16:30:00'
, numero = nextval('tblnotafiscal_numero_' || codfilial || '_' || serie || '_' || modelo || '_seq')
where numero = 0 and codnotafiscal in (837987, 837986, 837985)

-- ajusta total nao batendo com quantidade * unitario
update tblnotafiscalprodutobarra set valortotal = valorunitario * quantidade where codnotafiscal in (837987, 837986, 837985)

*/

with saida as (
	select sum(nf.valortotal) as total_saida
	from tblnotafiscal nf
	where nfeautorizacao is not null
	and nfecancelamento is null
	and nfeinutilizacao is null
	and codnaturezaoperacao = 15 -- Transferencia Saida
	and emissao > '2018-01-01'
), entrada as (
	select sum(nf.valortotal) as total_entrada
	from tblnotafiscal nf
	where emitida = false
	and codnaturezaoperacao = 16 -- Transferencia Entrada
	and emissao > '2018-01-01'
)
select saida.total_saida, entrada.total_entrada, saida.total_saida - entrada.total_entrada as diferenca from saida, entrada


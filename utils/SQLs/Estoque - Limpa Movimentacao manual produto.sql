-- LIMPA MOVIMENTOS DAS CONFERENCIAS
delete from tblestoquemovimento
where tblestoquemovimento.codestoquesaldoconferencia in (
	select esc.codestoquesaldoconferencia
	from tblprodutovariacao pv
	inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao)
	inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
	inner join tblestoquesaldoconferencia esc on (esc.codestoquesaldo = es.codestoquesaldo)
	where pv.codproduto = 35780
)

-- LIMPA CONFERENCIAS
delete from tblestoquesaldoconferencia
where tblestoquesaldoconferencia.codestoquesaldoconferencia in (
	select esc.codestoquesaldoconferencia
	from tblprodutovariacao pv
	inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao)
	inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
	inner join tblestoquesaldoconferencia esc on (esc.codestoquesaldo = es.codestoquesaldo)
	where pv.codproduto = 35780
)

-- LIMPA MOVIMENTOS MANUAIS
delete from tblestoquemovimento where tblestoquemovimento.codestoquemovimento in (
	select mov.codestoquemovimento
	from tblprodutovariacao pv
	inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao)
	inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
	inner join tblestoquemes mes on (mes.codestoquesaldo = es.codestoquesaldo)
	inner join tblestoquemovimento mov on (mov.codestoquemes = mes.codestoquemes)
	where mov.codestoquemovimentotipo in (1002, 1001)
	and mov.manual = true
	and es.fiscal = false
	and pv.codproduto = 35780
)

-- RECALCULAR SALDOS
select 'wget http://sistema.mgpapelaria.com.br/MGLara/estoque/calcula-custo-medio/' || mes.codestoquemes::varchar
from tblprodutovariacao pv
inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao)
inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
inner join tblestoquemes mes on (mes.codestoquesaldo = es.codestoquesaldo)
where pv.codproduto = 35780
and es.fiscal = false
order by mes.codestoquesaldo, mes.mes

-- VERIFICAR MANUALMENTE OUTROS MOVIMENTOS MANUAIS
select mov.codestoquemes, 'https://sistema.mgpapelaria.com.br/MGLara/estoque-mes/'||mov.codestoquemes::varchar--, mov.codestoquemovimentotipo, mov.entradaquantidade, mov.saidaquantidade, mov.data
from tblprodutovariacao pv
inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao)
inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
inner join tblestoquemes mes on (mes.codestoquesaldo = es.codestoquesaldo)
inner join tblestoquemovimento mov on (mov.codestoquemes = mes.codestoquemes)
where mov.manual = true
--and es.fiscal = false
and pv.codproduto = 35780
order by mov.entradaquantidade desc nulls last
limit 50


-- CONSERTA DATA ULTIMA CONFERENCIA
update tblestoquesaldo
set ultimaconferencia = (select max(esc.criacao) from tblestoquesaldoconferencia esc where esc.codestoquesaldo = tblestoquesaldo.codestoquesaldo)

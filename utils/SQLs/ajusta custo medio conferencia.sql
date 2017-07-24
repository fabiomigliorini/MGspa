-- conferencia de saldo com custo medio zerado
select * 
from tblestoquesaldoconferencia 
where coalesce(customedioinformado, 0) = 0

-- ajusta custo medio informado
update tblestoquesaldoconferencia
set customedioinformado = (
	select coalesce(pv.custoultimacompra, p.preco * 0.6)
	from tblestoquesaldo es 
	inner join tblestoquelocalprodutovariacao elpv on (elpv.codestoquelocalprodutovariacao = es.codestoquelocalprodutovariacao)
	inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao)
	inner join tblproduto p on (p.codproduto = pv.codproduto)
	where es.codestoquesaldo = tblestoquesaldoconferencia.codestoquesaldo
)
where coalesce(customedioinformado, 0) = 0

-- ajusta valor movimento com base na conferencia
update tblestoquemovimento
set saidavalor = round(tblestoquemovimento.saidaquantidade * esc.customedioinformado, 2)
, entradavalor = round(tblestoquemovimento.entradaquantidade * esc.customedioinformado, 2)
from tblestoquesaldoconferencia esc
where tblestoquemovimento.codestoquesaldoconferencia = esc.codestoquesaldoconferencia
and (
	tblestoquemovimento.saidavalor != round(tblestoquemovimento.saidaquantidade * esc.customedioinformado, 2)
	or tblestoquemovimento.entradavalor != round(tblestoquemovimento.entradaquantidade * esc.customedioinformado, 2)
)

-- movimentos manuais com valor zerado
update tblestoquemovimento
set saidavalor = round(tblestoquemovimento.saidaquantidade * coalesce(pv.custoultimacompra, p.preco * 0.6), 2)
, entradavalor = round(tblestoquemovimento.entradaquantidade * coalesce(pv.custoultimacompra, p.preco * 0.6), 2)
from tblestoquemes mes, tblestoquesaldo sld, tblestoquelocalprodutovariacao elpv, tblprodutovariacao pv, tblproduto p
where tblestoquemovimento.codestoquemes = mes.codestoquemes
and sld.codestoquesaldo = mes.codestoquesaldo
and elpv.codestoquelocalprodutovariacao = sld.codestoquelocalprodutovariacao
and pv.codprodutovariacao = elpv.codprodutovariacao
and p.codproduto = pv.codproduto
and sld.fiscal = false
and tblestoquemovimento.manual = true
and coalesce(tblestoquemovimento.entradavalor, 0) = 0
and coalesce(tblestoquemovimento.saidavalor, 0) = 0
--limit 100

-- meses que o total de movimento e diferente do total do mes
select mes.codestoquemes, mes.mes, mes.entradavalor, mov_totais.entradavalor, mes.saidavalor, mov_totais.saidavalor, 'wget http://192.168.1.205/MGLara/estoque/calcula-custo-medio/' || cast(mes.codestoquemes as varchar)
from tblestoquemes mes
inner join (
	select mov.codestoquemes, sum(mov.entradavalor) as entradavalor, sum(mov.saidavalor) as saidavalor
	from tblestoquemovimento mov
	group by mov.codestoquemes
	) mov_totais on (mov_totais.codestoquemes = mes.codestoquemes)
where mes.entradavalor != mov_totais.entradavalor
or mes.saidavalor != mov_totais.saidavalor



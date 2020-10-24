
-- marca conferencias de estoeque como inativas
update tblestoquesaldoconferencia
set inativo = date_trunc('second', now()) 
where tblestoquesaldoconferencia.codestoquesaldoconferencia in (
	select esc.codestoquesaldoconferencia 
	from tblestoquesaldoconferencia esc
	inner join tblestoquesaldo es on (es.codestoquesaldo = esc.codestoquesaldo)
	inner join tblestoquelocalprodutovariacao elpv on (elpv.codestoquelocalprodutovariacao = es.codestoquelocalprodutovariacao)
	inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao )
	where pv.codproduto = 002370
)

-- marca como nao conferido
update tblestoquesaldo 
set ultimaconferencia = null
where codestoquesaldo in (
	select esc.codestoquesaldo
	from tblestoquesaldoconferencia esc
	inner join tblestoquesaldo es on (es.codestoquesaldo = esc.codestoquesaldo)
	inner join tblestoquelocalprodutovariacao elpv on (elpv.codestoquelocalprodutovariacao = es.codestoquelocalprodutovariacao)
	inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao )
	where pv.codproduto = 002370
)

-- apaga movimentos das conferencias de estoque
delete from tblestoquemovimento 
where tblestoquemovimento.codestoquesaldoconferencia in (
	select codestoquesaldoconferencia
	from tblestoquesaldoconferencia esc
	where esc.inativo is not null
)

-- apaga movimentos de transferencia de entrada manuais
delete from tblestoquemovimento 
where tblestoquemovimento.manual 
and tblestoquemovimento.codestoquemovimento in (
	select mov.codestoquemovimento 
	from tblestoquemovimento mov
	inner join tblestoquemes mes on (mes.codestoquemes = mov.codestoquemes )
	inner join tblestoquesaldo sld on (sld.codestoquesaldo = mes.codestoquesaldo)
	inner join tblestoquelocalprodutovariacao elpv on (elpv.codestoquelocalprodutovariacao = sld.codestoquelocalprodutovariacao)
	inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao )
	where mov.manual = true
	and pv.codproduto = 002370
	and mov.codestoquemovimentoorigem is not null
)

-- esses tem que apagar  na mao (envolve outros produtos)
select mov.codestoquemovimento, mov.codestoquemes 
from tblestoquemovimento mov
inner join tblestoquemes mes on (mes.codestoquemes = mov.codestoquemes )
inner join tblestoquesaldo sld on (sld.codestoquesaldo = mes.codestoquesaldo)
inner join tblestoquelocalprodutovariacao elpv on (elpv.codestoquelocalprodutovariacao = sld.codestoquelocalprodutovariacao)
inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao )
inner join tblestoquemovimento dest on (dest.codestoquemovimentoorigem = mov.codestoquemovimento)
where mov.manual = true
and pv.codproduto = 002370

-- apaga restante dos movimentos manuais
delete from tblestoquemovimento 
where tblestoquemovimento.manual 
and tblestoquemovimento.codestoquemovimento in (
	select mov.codestoquemovimento 
	from tblestoquemovimento mov
	inner join tblestoquemes mes on (mes.codestoquemes = mov.codestoquemes )
	inner join tblestoquesaldo sld on (sld.codestoquesaldo = mes.codestoquesaldo)
	inner join tblestoquelocalprodutovariacao elpv on (elpv.codestoquelocalprodutovariacao = sld.codestoquelocalprodutovariacao)
	inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao )
	where mov.manual = true
	and pv.codproduto = 002370
)

select 'wget https://sistema.mgpapelaria.com.br/MGLara/estoque/calcula-custo-medio/' || mes.codestoquemes
from tblestoquemes mes
inner join tblestoquesaldo sld on (sld.codestoquesaldo = mes.codestoquesaldo)
inner join tblestoquelocalprodutovariacao elpv on (elpv.codestoquelocalprodutovariacao = sld.codestoquelocalprodutovariacao)
inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao )
where pv.codproduto = 002370
order by mes.mes, mes.codestoquesaldo




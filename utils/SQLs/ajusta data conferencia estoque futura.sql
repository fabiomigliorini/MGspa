-- conferencia com data futura
select * 
from tblestoquesaldoconferencia 
where date_trunc('day', data) > date_trunc('day', criacao)

update tblestoquesaldoconferencia 
set data = criacao
where date_trunc('day', data) > date_trunc('day', criacao)

-- data do movimento diferente da conferencia
select * 
from tblestoquesaldoconferencia esc
inner join tblestoquemovimento mov on (mov.codestoquesaldoconferencia = esc.codestoquesaldoconferencia)
where mov.data != esc.data

update tblestoquemovimento
set data = esc.data
from tblestoquesaldoconferencia esc
where tblestoquemovimento.codestoquesaldoconferencia = esc.codestoquesaldoconferencia
and tblestoquemovimento.data != esc.data

-- movimentos para alterar o mes do estoque
select 
	mov.codestoquemovimento, 
	mov.data, 
	mov.codestoquemes, 
	mes_correto.codestoquemes
from tblestoquemovimento mov
inner join tblestoquemes mes on (mes.codestoquemes = mov.codestoquemes)
left join tblestoquemes mes_correto on (mes_correto.codestoquesaldo = mes.codestoquesaldo and date_trunc('month', mov.data) = date_trunc('month', mes_correto.mes))
where mov.codestoquemovimentotipo = 1002
and date_trunc('month', mov.data) != date_trunc('month', mes.mes)

-- cria meses que nao existem
insert into tblestoquemes (codestoquesaldo, mes)
select distinct
	mes.codestoquesaldo, 
	date_trunc('month', mov.data)
from tblestoquemovimento mov
inner join tblestoquemes mes on (mes.codestoquemes = mov.codestoquemes)
left join tblestoquemes mes_correto on (mes_correto.codestoquesaldo = mes.codestoquesaldo and date_trunc('month', mov.data) = date_trunc('month', mes_correto.mes))
where mov.codestoquemovimentotipo = 1002
and date_trunc('month', mov.data) != date_trunc('month', mes.mes)
and mes_correto.codestoquemes is null

-- ajusta codestoquemes do movimento
update tblestoquemovimento
set codestoquemes = (
	select mes_certo.codestoquemes
	from tblestoquemes mes_certo 
	where mes_certo.codestoquesaldo = mes_errado.codestoquesaldo 
	and mes_certo.mes = date_trunc('month', tblestoquemovimento.data)
)
from tblestoquemes mes_errado
where mes_errado.codestoquemes = tblestoquemovimento.codestoquemes
and tblestoquemovimento.codestoquemovimentotipo = 1002
and date_trunc('month', tblestoquemovimento.data) != date_trunc('month', mes_errado.mes)

-- meses que o total de movimento e diferente do total do mes
select mes.codestoquemes, mes.mes, mes.entradaquantidade, mov_totais.entradaquantidade, mes.saidaquantidade, mov_totais.saidaquantidade, 'http://192.168.1.205/MGLara/estoque/calcula-custo-medio/' || cast(mes.codestoquemes as varchar)
from tblestoquemes mes
inner join (
	select mov.codestoquemes, sum(mov.entradaquantidade) as entradaquantidade, sum(mov.saidaquantidade) as saidaquantidade
	from tblestoquemovimento mov
	group by mov.codestoquemes
	) mov_totais on (mov_totais.codestoquemes = mes.codestoquemes)
where mes.entradaquantidade != mov_totais.entradaquantidade
or mes.saidaquantidade != mov_totais.saidaquantidade
limit 100
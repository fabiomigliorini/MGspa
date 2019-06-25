-- movimentacao do mes nao bate com totais
with mov as (
	select mov.codestoquemes, sum(saidaquantidade) saidaquantidade, sum(entradaquantidade) entradaquantidade
	from tblestoquemovimento mov
	group by mov.codestoquemes
)
select mes.codestoquemes
from tblestoquemes mes 
left join mov on (mes.codestoquemes = mov.codestoquemes)
where mes.codestoquesaldo in (
	select es.codestoquesaldo
	from tblestoquelocalprodutovariacao elpv
	inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
	where elpv.codprodutovariacao = 305
	)
and coalesce(mes.saidaquantidade, 0) != coalesce(mov.saidaquantidade, 0)
and coalesce(mes.entradaquantidade, 0) != coalesce(mov.entradaquantidade, 0)
limit 10


-- SALDO INICIAL MES NAO BATE COM FINAL DO MES ANTERIOR
select 
	mes.codestoquemes, 
	mes.mes, 
	mes.inicialquantidade, 
	(select m2.saldoquantidade from tblestoquemes m2 where m2.codestoquesaldo = mes.codestoquesaldo and m2.mes < mes.mes order by m2.mes desc limit 1) as anterior,
	mes.saldoquantidade,
	mes.*
from tblestoquemes mes 
where mes.codestoquesaldo in (
	select es.codestoquesaldo
	from tblestoquelocalprodutovariacao elpv
	inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
	where elpv.codprodutovariacao = 305
	)
and coalesce(mes.inicialquantidade, 0) != 
	coalesce((select m2.saldoquantidade from tblestoquemes m2 where m2.codestoquesaldo = mes.codestoquesaldo and m2.mes < mes.mes order by m2.mes desc limit 1), 0)
limit 10

-- CUSTO MEDIO ENTRADA DIFERENTE DA SAIDA
select ', ' || em.codestoquemovimento::varchar || ', ' || em.codestoquemovimentoorigem::varchar, em.codestoquemes, em.entradaquantidade, em.saidaquantidade --* 
from tblestoquemovimento em
inner join tblestoquemovimento emo on (em.codestoquemovimentoorigem = emo.codestoquemovimento)
where em.codestoquemes = emo.codestoquemes
and em.manual

--TODO: QUANTIDADE ENTRADA DIFERENTE SAIDA

--TODO: SAIDA TRANSFERENCIA SEM ENTRADA TRANSFERENCIA

-- DIFERENCA MAIOR QUE 2% ENTRE CUSTO DE ORIGEM E DESTINO
with mov as (
	select 
		em.codestoquemes, 
		emo.codestoquemes as codestoquemes_origem,
		coalesce(em.entradaquantidade, 0) - coalesce(em.saidaquantidade, 0) as quantidade, 
		coalesce(emo.saidaquantidade, 0) - coalesce(emo.entradaquantidade, 0) as quantidade_origem, 
		coalesce(em.entradavalor, 0) - coalesce(em.saidavalor, 0) as valor, 
		coalesce(emo.saidavalor, 0) - coalesce(emo.entradavalor, 0) as valor_origem
	from tblestoquemovimento em
	inner join tblestoquemovimento emo on (em.codestoquemovimentoorigem = emo.codestoquemovimento)
	where em.codestoquemovimentotipo in (4101, 4201)
	and (coalesce(emo.saidavalor, 0) - coalesce(emo.entradavalor, 0)) > 0
)
select 
	*, 
	abs(1-coalesce(mov.valor, 0) / coalesce(mov.valor_origem, 0)) as perc_dif,
	'wget http://sistema.mgpapelaria.com.br/MGLara/estoque/calcula-custo-medio/' || mov.codestoquemes::varchar
from mov 
where abs(1-coalesce(mov.valor, 0) / coalesce(mov.valor_origem, 0)) >= 0.1


-- SAIDAS COM CUSTO MEDIO INCORRETO
with mov as (
	select 
		em.codestoquemes, 
		em.saidaquantidade, 
		em.saidavalor, 
		(coalesce(mes.customedio, 0) * coalesce(em.saidaquantidade, 0))::numeric(14,2) as calculado
	from tblestoquemovimento em
	inner join tblestoquemes mes on (mes.codestoquemes = em.codestoquemes)
	inner join tblestoquemovimentotipo t on (t.codestoquemovimentotipo = em.codestoquemovimentotipo)
	where t.preco = 2
	and (coalesce(mes.customedio, 0) * coalesce(em.saidaquantidade, 0))::numeric(14,2) != 0
)
select * 
from mov
where abs(1- (coalesce(mov.saidavalor, 0) / coalesce(mov.calculado, 1))) > 0.02
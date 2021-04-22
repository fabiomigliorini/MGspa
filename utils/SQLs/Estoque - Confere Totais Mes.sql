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

-- Transferencia pro mesmo local
select ', ' || em.codestoquemovimento::varchar || ', ' || em.codestoquemovimentoorigem::varchar, em.codestoquemes, em.entradaquantidade, em.saidaquantidade --* 
from tblestoquemovimento em
inner join tblestoquemovimento emo on (em.codestoquemovimentoorigem = emo.codestoquemovimento)
where em.codestoquemes = emo.codestoquemes
and em.manual

/*
 * 
-- Apaga Transferencias pro mesmo local
Delete from tblestoquemovimento where codestoquemovimento in (
null
, 9135763, 9135762
, 9377425, 9377424
, 9377427, 9377426
, 7762407, 7762406
, 9617523, 9617522
, 9732873, 9732872
, 969165, 969164
, 2966508, 2966507
, 9617517, 9617516
, 9135755, 9135754
, 9377429, 9377428
, 9135771, 9135770
, 9377431, 9377430
, 10029283, 10029282
, 10511582, 10511581
, 10511579, 10511578
, 10517679, 10517678
, 9616621, 9616620
, 8995654, 8995653
, 8991939, 8991938
, 9732867, 9732866
, 10511575, 10511574
, 10222827, 10222826
, 10029274, 10029273
, 10029278, 10029277
, 9616626, 9616625
, 9617519, 9617518
, 9732869, 9732868
, 10222830, 10222829
, 10511577, 10511576
, 9616630, 9616629
, 10222834, 10222833
, 8991944, 8991943
, 9135567, 9135566
, 9526026, 9526025
, 10029287, 10029286
, 9616636, 9616635
, 9617525, 9617524
, 9732871, 9732870
, 10222836, 10222835
, 6389553, 6389552
, 6389551, 6389550
, 6625576, 6625575
, 3623746, 3623745
, 3874005, 3874004
, 7983428, 7983427
, 7021608, 7021607
, 7254923, 7254922
, 7476848, 7476847
, 7983442, 7983441
, 6354914, 6354913
, 7762140, 7762139
, 7762343, 7762342
, 9526142, 9526141
, 9135909, 9135908
, 9135905, 9135904
, 9135901, 9135900
, 9524938, 9524937
, 9524934, 9524933
, 9524936, 9524935
, 9524942, 9524941
, 9525534, 9525533
, 12446133, 12446132
, 12446076, 12446075
, 12446416, 12446415
, 12661304, 12661303
, 12663878, 12663877
, 12669644, 12669643
, 12671389, 12671388
, 12678477, 12678476
, 12678472, 12678471
, 969157, 969156
, 12446219, 12446218
, 13345716, 13345715
, 13345748, 13345747
, 8237086, 8237085
, 6625800, 6625799
, 8730172, 8730171
, 8475111, 8475110
, 7477374, 7477373
, 8730164, 8730163
, 12446102, 12446101
, 12446405, 12446404
, 12445841, 12445840
, 15069600, 15069599
, 15076166, 15076165
, 12445864, 12445863
, 2955050, 2955049
, 12446398, 12446397
, 12446306, 12446305
, 12445813, 12445812
, 12446030, 12446029
, 12446068, 12446067
, 6624956, 6624955
, 12446380, 12446379
, 2958116, 2958115
, 8995657, 8995656
, 8995659, 8995658
, 8995663, 8995662
, 8991970, 8991969
, 8991941, 8991940
, 9135583, 9135582
, 9135559, 9135558
, 9135575, 9135574
)
  
 * 
 * 
 */

--TODO: CUSTO MEDIO ENTRADA DIFERENTE DA SAIDA

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
select * , 	'wget http://sistema.mgpapelaria.com.br/MGLara/estoque/calcula-custo-medio/' || mov.codestoquemes::varchar
from mov
where abs(coalesce(mov.saidavalor, 0) - coalesce(mov.calculado, 1)) > 0.02

-- movimentacao do mes nao bate com totais
/*
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
*/

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

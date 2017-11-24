
-- select * from tblestoquemes limit 50
select mes.codestoquemes, mes.entradaquantidade, sum(mov.entradaquantidade), mes.saidaquantidade, sum(mov.saidaquantidade)
from tblestoquemes mes
inner join tblestoquemovimento mov on (mov.codestoquemes = mes.codestoquemes)
group by mes.codestoquemes, mes.entradaquantidade, mes.saidaquantidade
having mes.entradaquantidade != sum(mov.entradaquantidade)
    or mes.saidaquantidade != sum(mov.saidaquantidade)


-- RECALCULA CUSTO MEDIO DE TODO ESTOQUE CENTRO ANTIGA
select 
    --es.codestoquesaldo, 
    'wget http://192.168.1.205/MGLara/estoque/calcula-custo-medio/' || cast( (
            select mes.codestoquemes
            from tblestoquemes mes
            where mes.codestoquesaldo = es.codestoquesaldo
            order by mes asc
            limit 1
        ) as varchar)
from tblestoquelocalprodutovariacao elpv 
inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
where elpv.codestoquelocal = 103001
--and es.saldoquantidade != 0
order by es.codestoquesaldo

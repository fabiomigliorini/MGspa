with meses as (
SELECT date_trunc('month', dd):: date as mes
FROM generate_series
        ( now() - '1 year'::interval
        , now() - '1 month'::interval
        , '1 month'::interval) dd
),
venda_mes as (
select elpvv.mes, sum(quantidade) as quantidade
from tblprodutovariacao pv
inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao)
inner join tblestoquelocalprodutovariacaovenda elpvv on (elpvv.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
where elpvv.mes > now() - '13 months'::interval
--and pv.codproduto = 25095
--and pv.codproduto = 3319
--and pv.codproduto = 317753
and pv.codproduto = 26556
--and pv.codprodutovariacao = 26556
--and elpv.codestoquelocal = 104001
group by elpvv.mes
)
select meses.mes, coalesce(cast(venda_mes.quantidade as bigint), 0) as quantidade
from meses
left join venda_mes on (venda_mes.mes = meses.mes)
order by meses.mes desc


/*
select elpvv.mes, sum(quantidade) as quantidade
from tblprodutovariacao pv
inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao)
inner join tblestoquelocalprodutovariacaovenda elpvv on (elpvv.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
where pv.codproduto = 25095
group by elpvv.mes
*/
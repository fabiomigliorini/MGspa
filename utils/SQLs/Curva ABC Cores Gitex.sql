
select p.codproduto, p.produto, pv.codprodutovariacao, pv.variacao, vda.quantidade, vda.valor, sld.saldoquantidade
from tblproduto p
inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
left join (
    select elpv.codprodutovariacao, sum(es.saldoquantidade) as saldoquantidade
    from tblestoquelocalprodutovariacao elpv
    inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
    group by elpv.codprodutovariacao
    ) sld on (sld.codprodutovariacao = pv.codprodutovariacao)
left join (
    select elpv.codprodutovariacao, sum(v.quantidade) as quantidade, sum(v.valor) as valor
    from tblestoquelocalprodutovariacao elpv
    inner join tblestoquelocalprodutovariacaovenda v on (v.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and mes >= '2015-01-01')
    group by elpv.codprodutovariacao
    ) vda on (vda.codprodutovariacao = pv.codprodutovariacao)
where p.inativo is null
and p.produto ilike '%gitex%'


/*
update tblprodutovariacao set variacao = '192 - Camomila' where variacao ilike '192 - Camolila';

select pv.variacao, count(*) 
from tblprodutovariacao pv
inner join tblproduto p on (p.codproduto = pv.codproduto)
where p.produto ilike '%gitex%'
group by pv.variacao 
order by pv.variacao

*/
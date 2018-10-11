select elpvv.mes, sum(elpvv.valor) as valor, sum(elpvv.quantidade) as quantidade
from tblestoquelocalprodutovariacaovenda elpvv
inner join tblestoquelocalprodutovariacao elpv on (elpv.codestoquelocalprodutovariacao = elpvv.codestoquelocalprodutovariacao)
inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao)
inner join tblproduto p on (p.codproduto = pv.codproduto)
where p.codmarca = 2
group by elpvv.mes
--limit 50
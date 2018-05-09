select elpv.codestoquelocal, sum(es.saldoquantidade * p.preco) as venda, sum(es.saldovalor) as custo
from tblproduto p
inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao)
inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
where es.saldoquantidade > 0
group by elpv.codestoquelocal
order by 1
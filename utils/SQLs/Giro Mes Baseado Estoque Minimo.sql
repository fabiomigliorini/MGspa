select p.codproduto, p.produto, pv.variacao, coalesce(pv.referencia, p.referencia) as referencia, sum(elpv.estoquemaximo) as giro_mes
from tblproduto p
inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao)
where elpv.codestoquelocal in (102001, 103001, 104001)
and p.produto ilike '%bobina%80%'
group by p.codproduto, p.produto, pv.variacao, coalesce(pv.referencia, p.referencia) 
order by 5 desc nulls last
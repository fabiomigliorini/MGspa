-- APAGAR
/*
select p.codproduto, pv.codprodutovariacao, p.produto, pv.variacao
from tblprodutovariacao pv
inner join tblproduto p on (p.codproduto = pv.codproduto)
where (pv.variacao ilike '%apaga%'
or pv.variacao ilike '%elimina%'
or pv.variacao ilike '%exclui%')
and pv.variacao not ilike '%papagaio%'
order by p.produto, pv.variacao
*/

--select distinct coalesce(pv.variacao, '{NULL}') from tblprodutovariacao pv order by 1 asc

-- VARIACAO EM BRANCO QUANDO EXISTE OUTRAS PREENCHIDAS
select p.codproduto, pv.codprodutovariacao, p.produto, pv.variacao
from tblprodutovariacao pv
inner join tblproduto p on (p.codproduto = pv.codproduto)
where pv.variacao is null
and pv.codproduto in (
		select pv2.codproduto 
		from tblprodutovariacao pv2
		group by pv2.codproduto
		having count(pv2.codprodutovariacao) > 1
	)
--and pv.variacao not ilike '%papagaio%'
order by p.produto, pv.variacao


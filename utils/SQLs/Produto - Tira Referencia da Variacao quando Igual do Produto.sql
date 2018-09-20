update tblprodutovariacao
set referencia = null
where codprodutovariacao in (
	select pv.codprodutovariacao
	from tblprodutovariacao pv
	inner join tblproduto p on (p.codproduto = pv.codproduto)
	where pv.codproduto in 
		(
			select p2.codproduto --, count(p2.codprodutovariacao)
			from tblprodutovariacao p2
			group by p2.codproduto
			having count(p2.codprodutovariacao) = 1
		)
	and p.referencia = pv.referencia
)
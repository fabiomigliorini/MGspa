--select * from tblnotafiscalprodutobarra where codprodutobarra = 971032


--update tblproduto set produto = replace(produto, 'Lapis Preto Bic 0 ', 'Lapis Preto Bic ') where produto ilike 'Lapis Preto Bic 0%'

select p.codproduto, produto
from tblproduto p
left join tblprodutobarra pb on (pb.codproduto = p.codproduto)
where pb.codprodutobarra is null
--and produto ilike '% bic %'
order by produto

--delete from tblprodutoembalagem where codproduto in (
--delete from tblprodutoimagem where codproduto in (
--delete from tblpranchetaproduto where codproduto in (
delete from tblproduto where codproduto in (
	select p.codproduto
	from tblproduto p
	left join tblprodutobarra pb on (pb.codproduto = p.codproduto)
	left join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
	where pb.codprodutobarra is null
	and pv.codprodutovariacao is null
	order by produto
)
update tblprodutovariacao
set descontinuado = date_trunc('seconds', now())
where codproduto in (930820, 23064, 24151)
and variacao ilike '127%'
and descontinuado is null


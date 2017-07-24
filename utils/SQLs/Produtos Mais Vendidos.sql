--select * from tblestoquelocalprodutovariacao limit 50

select row_number() over() as Lugar, * from (
	select p.codproduto as cod, p.produto, um.sigla as un, p.preco, m.marca, p.inativo, venda.vendaanovalor as valor, venda.vendaanoquantidade as qtd, cast(venda.vendaanovalor / venda.vendaanoquantidade as numeric(14,4)) as media
	from tblproduto p 
	inner join tblunidademedida um on (um.codunidademedida = p.codunidademedida)
	left join tblmarca m on (m.codmarca = p.codmarca)
	left join (
		select sum(elpv.vendaanovalor) vendaanovalor, sum(elpv.vendaanoquantidade) as vendaanoquantidade, pv.codproduto
		from tblestoquelocalprodutovariacao elpv
		inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao)
		group by pv.codproduto
		) venda on (venda.codproduto = p.codproduto)
	order by venda.vendaanovalor desc nulls last
	limit 2000

) x
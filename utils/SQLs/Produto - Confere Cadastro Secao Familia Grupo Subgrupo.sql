select 
	p.codproduto,
	p.produto,
	array_to_string(array(
		select pb.barras
		from tblprodutobarra pb
		where pb.codproduto = p.codproduto
		and pb.codprodutoembalagem is null
		order by pb.barras
		--limit 1
	), ' / ') AS barras,	
	array_to_string(array(
		select pb.barras || ' C/' || round(pe.quantidade, 0)
		from tblprodutobarra pb
		inner join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
		where pb.codproduto = p.codproduto
		order by pe.quantidade, pb.barras
		--limit 1
	), ' | ') AS barras_emb,	
	sp.secaoproduto,
	fp.familiaproduto,
	gp.grupoproduto,
	sgp.subgrupoproduto,
	sgp.codsubgrupoproduto
from tblproduto p
inner join tblsubgrupoproduto sgp on (sgp.codsubgrupoproduto = p.codsubgrupoproduto)
inner join tblgrupoproduto gp on (gp.codgrupoproduto = sgp.codgrupoproduto)
inner join tblfamiliaproduto fp on (fp.codfamiliaproduto = gp.codfamiliaproduto)
inner join tblsecaoproduto sp on (sp.codsecaoproduto = fp.codsecaoproduto)
where (p.produto ilike 'bateria%' or p.produto ilike 'pilha%' or p.produto ilike '*%bateria%' or p.produto ilike '*%pilha%')
--and produto ilike '%fone%'
--order by p.produto 
order by grupoproduto, subgrupoproduto, produto

/*
update tblproduto set produto = replace (produto, 'Pilha', 'Bateria') where codsubgrupoproduto = 3189 and produto ilike 'Pilha%'

update tblproduto set codsubgrupoproduto = 3189 where codproduto in (311257,
305878,
305877
)


select * from tblnegocioprodutobarra where codprodutobarra = 32713 order by criacao desc, codnegocioprodutobarra desc
select * from tblnotafiscalprodutobarra where codprodutobarra = 32713 order by criacao desc, codnotafiscalprodutobarra desc

update tblnegocioprodutobarra set codprodutobarra = 32717 where codprodutobarra = 32713
*/
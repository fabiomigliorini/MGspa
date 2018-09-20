
/*

-- PRODUTOS SEM A MARCA NA DESCRICAO
select codproduto, produto, produto || ' Roma Jensen' from tblproduto where codmarca = 161 and produto not ilike '%Roma Jensen%'

-- ADICIONA DESCRICAO DA MARCA NOS PRODUTOS SEM A MARCA NA DESCRICAO
update tblproduto 
set produto = produto || ' Roma Jensen'
where codmarca = 161 
and produto not ilike '%Roma Jensen%'

-- CONFERE O CODIGO DA MARCA NOS PRODUTOS COM A MARCA NA DESCRICAO
select codmarca, min(produto), min(codproduto), count(*)
from tblproduto
where produto ilike '% Roma Jensen %'
group by codmarca

-- AJUSTA MARCA
update tblproduto 
set codmarca = 161
where produto ilike '%Roma Jensen%'
and codmarca = 10000299


-- CONFERE O CODIGO DE BARRAS DA MARCA
select substring(pb.barras from 1 for 7), min(pb.barras), max(pb.barras), count(*)
from tblproduto p
inner join tblprodutobarra pb on (pb.codproduto = p.codproduto) 
where p.codmarca = 161
and pb.barras not ilike '234%'
group by substring(pb.barras from 1 for 7)
order by 1

-- CONFERE CODIGO DA MARCA DOS PRODUTOS QUE COMECAM COM O CODIGO DE BARRAS DA MARCA
select p.codmarca, min(pb.barras), max(pb.barras), count(*)
from tblproduto p
inner join tblprodutobarra pb on (pb.codproduto = p.codproduto) 
where pb.barras ilike '%7896965%'
group by p.codmarca

-- Bate Codigo de Barras com Referencia (Roma Jensen)
select 
	p.codproduto, 
	p.produto, 
	pv.variacao, 
	pb.barras, 
	coalesce(pv.referencia, p.referencia) as ref_cadastro, 
	substr(pb.barras,length(pb.barras)-4, 4) as ref_barras
from tblproduto p
inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
inner join tblprodutobarra pb on (pb.codprodutovariacao = pv.codprodutovariacao)
where p.codmarca = 161
and coalesce(coalesce(pv.referencia, p.referencia), '') != substr(pb.barras,length(pb.barras)-4, 4)

-- Bate Codigo de Barras com Referencia (Jandaia)
select 
	p.codproduto, 
	p.produto, 
	pv.variacao, 
	pb.barras, 
	substr(coalesce(pv.referencia, p.referencia), 1, 5) as ref_cadastro, 
	substr(pb.barras,length(pb.barras)-5, 5) as ref_barras
from tblproduto p
inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
inner join tblprodutobarra pb on (pb.codprodutovariacao = pv.codprodutovariacao)
where p.codmarca = 30000046
and pb.codprodutoembalagem is null
and 	substr(coalesce(pv.referencia, p.referencia), 1, 5) != substr(pb.barras,length(pb.barras)-5, 5) 
order by p.produto, p.codproduto, pv.variacao, pv.codprodutovariacao

*/



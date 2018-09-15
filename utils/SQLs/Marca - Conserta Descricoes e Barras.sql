
/*

-- PRODUTOS SEM A MARCA NA DESCRICAO
select produto, produto || ' Foroni' from tblproduto where codmarca = 51 and produto not ilike '%Foroni%'

-- ADICIONA DESCRICAO DA MARCA NOS PRODUTOS SEM A MARCA NA DESCRICAO
update tblproduto 
set produto = produto || ' Foroni'
where codmarca = 51 
and produto not ilike '%Foroni%'

-- CONFERE O CODIGO DA MARCA NOS PRODUTOS COM A MARCA NA DESCRICAO
select distinct codmarca 
from tblproduto
where produto ilike '%Foroni%'

-- AJUSTA MARCA
update tblproduto 
set codmarca = 51
where produto ilike '%Foroni%'
and codmarca = 10000299


-- CONFERE O CODIGO DE BARRAS DA MARCA
select substring(pb.barras from 1 for 7), min(pb.barras), max(pb.barras), count(*)
from tblproduto p
inner join tblprodutobarra pb on (pb.codproduto = p.codproduto) 
where p.codmarca = 51
and pb.barras not ilike '234%'
group by substring(pb.barras from 1 for 7)
order by 1

-- CONFERE CODIGO DA MARCA DOS PRODUTOS QUE COMECAM COM O CODIGO DE BARRAS DA MARCA
select p.codmarca, min(pb.barras), max(pb.barras), count(*)
from tblproduto p
inner join tblprodutobarra pb on (pb.codproduto = p.codproduto) 
where pb.barras ilike '%7899264%'
group by p.codmarca

*/




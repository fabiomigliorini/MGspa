select m.marca, substr(pb.barras, 1, 8) as barras, count(*)
from tblproduto p
inner join tblprodutobarra pb on (pb.codproduto = p.codproduto)
left join tblmarca m on (m.codmarca = p.codmarca)
where p.produto ilike '%colorprint%'
group by m.marca, substr(pb.barras, 1, 8)
order by 1, 2


/*
select m.marca, pb.barras, p.produto, p.codproduto
from tblproduto p
inner join tblprodutobarra pb on (pb.codproduto = p.codproduto)
left join tblmarca m on (m.codmarca = p.codmarca)
where pb.barras ilike '7898178%'
order by 1, 2
*/

--select criacao, * from tblnfeterceiroitem where cean ilike '%7898476326129%' or cean ilike '%7898506457717%' order by criacao

/*
select * from tblnegocioprodutobarra where codprodutobarra = 30016311

update tblnegocioprodutobarra set codprodutobarra = 70063 where codprodutobarra = 69204
update tblnotafiscalprodutobarra set codprodutobarra = 70063 where codprodutobarra = 69204
update tblnfeterceiroitem set codprodutobarra = 70063 where codprodutobarra = 69204
delete from tblprodutobarra where codprodutobarra = 69204
*/
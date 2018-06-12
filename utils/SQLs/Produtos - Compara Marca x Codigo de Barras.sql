select * from tblnfeterceiroitem where '7898461574450' in (cean, ceantrib)

select * from tblnfeterceiroitem where cean ilike '7898461555985%' 


select npb.criacao, npb.*
from tblnegocioprodutobarra npb
inner join tblprodutobarra pb on (pb.codprodutobarra = npb.codprodutobarra)
where pb.barras = '7898432956933'
order by npb.criacao desc nulls last


select * from tblnfeterceiroitem where cprod ilike '%M%10%'



select substring(pb.barras from 0 for 8), min(pb.codproduto), count(*)
from tblprodutobarra pb
inner join tblproduto p on (p.codproduto = pb.codproduto)
inner join tblmarca m on (m.codmarca = p.codmarca)
where pb.barras not ilike '2340%'
and m.marca ilike 'tilibra%'
group by substring(pb.barras from 0 for 8)
order by 1 asc

select m.marca, substring(pb.barras from 1 for 7), p.codproduto, produto
from tblprodutobarra pb
inner join tblproduto p on (p.codproduto = pb.codproduto)
inner join tblmarca m on (m.codmarca = p.codmarca)
where substring(pb.barras from 1 for 7) in ('7891027', '1789102', '2789102', '3789102')
and m.marca not ilike 'Tilibra%'
order by 1


/*
select min(p.codproduto), min(p.produto), SUBSTR(p.produto,1, POSITION(' ' IN p.produto)) 
from tblproduto p
where p.codmarca = 1
group by SUBSTR(p.produto,1, POSITION(' ' IN p.produto)) 
order by 3 asc


select produto, replace(produto, 'Tilibra', 'Tilibra Impressos') from tblproduto where codmarca = 419 and produto not ilike '%tilibra%impressos%'

update tblproduto 
set produto = replace(produto, 'Tilibra', 'Tilibra Impressos') 
where codmarca = 419 and produto not ilike '%tilibra%impressos%'



update tblproduto
set codmarca = 1 
where codmarca = 419 
and produto ilike 'fichario%'

update tblproduto 
set codmarca = 419
where codmarca = 1 
and produto ilike 'Talao%'
or produto ilike 'Termo%'
or produto ilike 'Vale%'

*/
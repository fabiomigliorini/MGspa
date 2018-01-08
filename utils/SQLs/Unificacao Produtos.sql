/*
select 
	p.inativo
	, (select count(pv.codprodutovariacao) from tblprodutovariacao pv where pv.codproduto = p.codproduto) as qtdvriacoes
	, * 
from tblproduto p
where p.produto ilike '%scrity%11%16%'
order by p.produto asc
*/

update tblnegocioprodutobarra set codprodutobarra = 935393 where codprodutobarra in (10012736);

update tblnotafiscalprodutobarra set codprodutobarra = 935393 where codprodutobarra in (10012736);

update tblcupomfiscalprodutobarra set codprodutobarra = 935393 where codprodutobarra in (10012736);

update tblnfeterceiroitem set codprodutobarra = 935393 where codprodutobarra in (10012736);

select * from tblnegocioprodutobarra where codprodutobarra = 10022639

select * from tblnotafiscalprodutobarra where codprodutobarra = 475
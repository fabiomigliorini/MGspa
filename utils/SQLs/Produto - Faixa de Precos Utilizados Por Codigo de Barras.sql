/*
-- NFETERCEIRO
select distinct
	p.produto, 
	pv.variacao,
	pb.barras,
	pb.codprodutobarra,
	pe.quantidade,
	min(nfpb.vuncom) as min,
	max(nfpb.vuncom) as max
from tblnfeterceiroitem nfpb
inner join tblprodutobarra pb on (pb.codprodutobarra = nfpb.codprodutobarra)
left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
inner join tblprodutovariacao pv on (pv.codprodutovariacao = pb.codprodutovariacao)
inner join tblproduto p on (p.codproduto = pv.codproduto)
where p.codproduto = 6914
group by 
	p.produto, 
	pv.variacao,
	pb.barras,
	pb.codprodutobarra,
	pe.quantidade
order by 
	p.produto, 
	pv.variacao,
	pe.quantidade nulls first,
	pb.barras,
	pb.codprodutobarra
limit 100
*/

/*

update tblnfeterceiroitem 
set codprodutobarra = 972812
where codprodutobarra in (970510, 31017)

*/

/*
-- NEGOCIOS
select distinct
	p.produto, 
--	pv.variacao,
--	pb.barras,
--	pb.codprodutobarra,
	pe.quantidade,
	min(nfpb.valorunitario) as min,
	max(nfpb.valorunitario) as max
from tblnegocioprodutobarra nfpb
inner join tblprodutobarra pb on (pb.codprodutobarra = nfpb.codprodutobarra)
left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
inner join tblprodutovariacao pv on (pv.codprodutovariacao = pb.codprodutovariacao)
inner join tblproduto p on (p.codproduto = pv.codproduto)
where p.codproduto = 6914
group by 
	p.produto, 
--	pv.variacao,
--	pb.barras,
--	pb.codprodutobarra,
	pe.quantidade
order by 
	p.produto, 
--	pv.variacao,
	pe.quantidade nulls first
--	pb.barras,
--	pb.codprodutobarra
limit 100
*/
/*

-- DIMINUI NEGOCIO
update tblnegocioprodutobarra
set codprodutobarra = 972812
where codprodutobarra = 31017
and valorunitario <= 1.0300000000

-- AUMENTA NEGOCIO
update tblnegocioprodutobarra
set codprodutobarra = 947229
where codprodutobarra = 30022507
and valorunitario >= 4.20

*/


/*

-- NOTAS FISCAIS
select distinct
	p.produto, 
--	pv.variacao,
	pe.quantidade,
--	pb.barras,
--	pb.codprodutobarra,
	min(nfpb.valorunitario) as min,
	max(nfpb.valorunitario) as max
from tblnotafiscalprodutobarra nfpb
inner join tblprodutobarra pb on (pb.codprodutobarra = nfpb.codprodutobarra)
left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
inner join tblprodutovariacao pv on (pv.codprodutovariacao = pb.codprodutovariacao)
inner join tblproduto p on (p.codproduto = pv.codproduto)
where p.codproduto = 6914
group by 
	p.produto, 
--	pv.variacao,
--	pb.barras,
--	pb.codprodutobarra,
	pe.quantidade
order by 
	p.produto, 
--	pv.variacao,
	pe.quantidade nulls first
--	pb.barras,
--	pb.codprodutobarra
limit 100

*/

/*

-- DIMINUI
update tblnotafiscalprodutobarra
set codprodutobarra = 30020769
where codprodutobarra = 10002316
and valorunitario <= 1.0300000000

-- AUMENTA
update tblnotafiscalprodutobarra
set codprodutobarra = 947229
where codprodutobarra = 30022507
and valorunitario >= 4.20

*/
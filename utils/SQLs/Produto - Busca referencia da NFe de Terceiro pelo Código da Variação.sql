-- PEGA REFERENCIA DA NFE DE TERCEIRO
select pb.codprodutovariacao, cprod, xprod, cean, count(*), min(nti.criacao), max(nti.criacao)
from tblnfeterceiroitem nti
inner join tblprodutobarra pb on (pb.codprodutobarra = nti.codprodutobarra)
--where pb.codproduto = 12752
-- where pb.codprodutovariacao between 13276 and 13277
where pb.codprodutovariacao = 9599
group by pb.codprodutovariacao, cprod, xprod, cean
order by 1,  7 asc

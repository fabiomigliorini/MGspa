-- PEGA REFERENCIA DA NFE DE TERCEIRO
select pb.codproduto, pb.codprodutovariacao, cprod, xprod, cean, count(*), min(nti.criacao), max(nti.criacao)
from tblnfeterceiroitem nti
inner join tblprodutobarra pb on (pb.codprodutobarra = nti.codprodutobarra)
--where pb.codproduto = 12752
-- where pb.codprodutovariacao between 13276 and 13277
where pb.codprodutovariacao = 8952
group by pb.codproduto, pb.codprodutovariacao, cprod, xprod, cean
order by 2,  8 desc

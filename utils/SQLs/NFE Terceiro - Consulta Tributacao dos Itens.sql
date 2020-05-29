select p.codtributacao, count(*), min(p.codproduto)
from tblnfeterceiroitem nti
inner join tblprodutobarra pb on (pb.codprodutobarra = nti.codprodutobarra)
inner join tblproduto p on (p.codproduto = pb.codproduto)
where nti.codnfeterceiro = 23135
group by p.codtributacao
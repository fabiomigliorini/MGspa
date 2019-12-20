select count(*), min(codnotafiscalprodutobarra), min(pb.barras)
from tblnotafiscalprodutobarra nfi
inner join tblprodutobarra pb on (pb.codprodutobarra = nfi.codprodutobarra)
where nfi.codnotafiscal = 1320244
group by pb.codprodutovariacao
having count(*) > 1

select count(*), min(codnotafiscalprodutobarra), min(pb.barras)
from tblnotafiscalprodutobarra nfi
inner join tblprodutobarra pb on (pb.codprodutobarra = nfi.codprodutobarra)
where nfi.codnotafiscal = 1320244
group by pb.codproduto
having count(*) > 1
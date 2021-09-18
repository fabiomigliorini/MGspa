select pe.quantidade, pb.barras, pb.codprodutobarra, count(*), min(n.lancamento), max(n.lancamento )
from tblprodutobarra pb
inner join tblnegocioprodutobarra npb on (npb.codprodutobarra  = pb.codprodutobarra )
inner join tblnegocio n on (n.codnegocio  = npb.codnegocio )
left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem )
where pb.codproduto = 026031
and n.lancamento >= '2019-01-01'
group by pe.quantidade , pb.barras, pb.codprodutobarra
order by 1 nulls first, 6 desc
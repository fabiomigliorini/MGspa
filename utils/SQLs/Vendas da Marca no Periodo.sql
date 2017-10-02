select p.codproduto, p.produto, pv.variacao,  sum(npb.quantidade * coalesce(pe.quantidade, 1)) as quant, sum(npb.valortotal) as valor
from tblproduto p
inner join tblmarca m on (m.codmarca = p.codmarca)
inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
inner join tblprodutobarra pb on (pb.codprodutovariacao = pv.codprodutovariacao)
left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
inner join tblnegocioprodutobarra npb on (npb.codprodutobarra = pb.codprodutobarra)
inner join tblnegocio n on (n.codnegocio = npb.codnegocio)
inner join tblnaturezaoperacao no on (no.codnaturezaoperacao = n.codnaturezaoperacao)
where m.marca ilike '%GITEX%'
and n.codnegociostatus =  2 -- fechado
and n.lancamento >= '2017-08-01'
and (no.venda = true or no.vendadevolucao = true)
group by p.codproduto, p.produto, pv.variacao
--limit 100

--select * from tblnaturezaoperacao
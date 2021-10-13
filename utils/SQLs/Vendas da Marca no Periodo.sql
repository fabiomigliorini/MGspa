select 
    --p.codproduto, p.produto, pv.variacao,
    n.codfilial,  
    ve.pessoa, 
    sum(npb.quantidade * coalesce(pe.quantidade, 1)) as quant, 
    sum(npb.valortotal) as valor
from tblproduto p
inner join tblmarca m on (m.codmarca = p.codmarca)
inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
inner join tblprodutobarra pb on (pb.codprodutovariacao = pv.codprodutovariacao)
left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
inner join tblnegocioprodutobarra npb on (npb.codprodutobarra = pb.codprodutobarra)
inner join tblnegocio n on (n.codnegocio = npb.codnegocio)
inner join tblnaturezaoperacao no on (no.codnaturezaoperacao = n.codnaturezaoperacao)
inner join tblpessoa ve on (ve.codpessoa = n.codpessoavendedor)
where m.marca ilike '%tigre%'
and n.codnegociostatus =  2 -- fechado
and n.lancamento between '2021-09-01' and '2021-09-30 23:59:59'
and (no.venda = true or no.vendadevolucao = true)
group by n.codfilial, ve.pessoa --, p.codproduto, p.produto, pv.variacao
order by 4 desc
--limit 100

--select * from tblnaturezaoperacao


select * from tblmovimentotitulo t where codtitulo = 384358
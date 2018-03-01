
select 
    n.lancamento,
    n.codnegocio,
    no.naturezaoperacao,
    pe.fantasia as cliente,
    p.produto,
    npb.quantidade,
    npb.valorunitario,
    (1-(coalesce(n.valortotal, 0) / coalesce(n.valorprodutos, 0))) as desconto,
    coalesce(npb.valortotal, 0) * (case when n.codoperacao = 1 then -1 else 1 end) * (coalesce(n.valortotal, 0) / coalesce(n.valorprodutos, 0)) as valortotal
from tblnegocio n
inner join tblnaturezaoperacao no on (no.codnaturezaoperacao = n.codnaturezaoperacao)
inner join tblpessoa pe on (pe.codpessoa = n.codpessoa)
inner join tblnegocioprodutobarra npb on (npb.codnegocio = n.codnegocio)
inner join tblprodutobarra pb on (pb.codprodutobarra = npb.codprodutobarra)
inner join tblproduto p on (p.codproduto = pb.codproduto)
where n.codnegociostatus = 2 -- fechado
and n.codpessoa not in (select distinct f2.codpessoa from tblfilial f2)
and n.codnaturezaoperacao in (1, 2) -- Venda / Devolucao de Vendas -- TODO: Fazer modelagem para tirar o codigo fixo
and p.codsubgrupoproduto = 2951 -- Xerox -- TODO: Fazer modelagem para tirar o codigo fixo
and n.lancamento between '2018-01-26' and '2018-02-28'
and n.codfilial = 103
order by lancamento, produto
--group by date_trunc('day', n.lancamento)
--order by date_trunc('day', n.lancamento)

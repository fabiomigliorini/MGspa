select 
    p.codproduto,
    p.produto, 
    pv.variacao,
    coalesce(pv.referencia, p.referencia) as referencia,
    pv.dataultimacompra as comprado,
    pv.quantidadeultimacompra::bigint as quant,
    pv.custoultimacompra as custo,
    p.preco as venda,
    saldo.saldo::bigint,
    ct_venda."2015", 
    ct_venda."2016", 
    ct_venda."2017"
from
    crosstab('
    select
        tblprodutobarra.codprodutovariacao
        , extract(year from tblnegocio.lancamento) as ano
        , sum(tblnegocioprodutobarra.quantidade * coalesce(tblprodutoembalagem.quantidade, 1) * (case when tblnaturezaoperacao.codoperacao = 1 then -1 else 1 end))::bigint as quantidade
        --, sum(tblnegocioprodutobarra.valortotal * (case when tblnaturezaoperacao.codoperacao = 1 then -1 else 1 end)) as valor
    from tblnegocio
    inner join tblnaturezaoperacao on (tblnaturezaoperacao.codnaturezaoperacao = tblnegocio.codnaturezaoperacao)
    inner join tblnegocioprodutobarra on (tblnegocioprodutobarra.codnegocio = tblnegocio.codnegocio)
    inner join tblprodutobarra on (tblprodutobarra.codprodutobarra = tblnegocioprodutobarra.codprodutobarra)
    left join tblprodutoembalagem on (tblprodutoembalagem.codprodutoembalagem = tblprodutobarra.codprodutoembalagem)
    where tblnegocio.codnegociostatus = 2 --Fechado
    and (tblnaturezaoperacao.venda = true or tblnaturezaoperacao.vendadevolucao = true)
    and tblprodutobarra.codproduto in (select tblproduto.codproduto from tblproduto where tblproduto.codmarca = 1) -- CODIGO DA MARCA
    and extract(month from tblnegocio.lancamento) in (1, 2, 3)
    and tblnegocio.lancamento >= ''2015-01-01''
    --and tblprodutobarra.codproduto = 023800
    group by
        tblprodutobarra.codprodutovariacao
        , extract(year from tblnegocio.lancamento) 
    order by 1, 2
    ', 
    'select y from generate_series(2015, 2017) y'
    ) AS ct_venda(codprodutovariacao bigint, "2015" bigint, "2016" bigint, "2017" bigint)
full join (
    select elpv.codprodutovariacao, sum(es.saldoquantidade) as saldo
    from tblestoquelocalprodutovariacao elpv
    inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
    inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao)
    inner join tblproduto p on (p.codproduto = pv.codproduto)
    where p.codmarca = 1 -- CODIGO DA MARCA
    group by elpv.codprodutovariacao
    having sum(es.saldoquantidade) != 0
    ) saldo on (saldo.codprodutovariacao = ct_venda.codprodutovariacao)
left join tblprodutovariacao pv on (pv.codprodutovariacao = coalesce(ct_venda.codprodutovariacao, saldo.codprodutovariacao))
left join tblproduto p on (p.codproduto = pv.codproduto)
order by 
    p.produto,
    pv.variacao


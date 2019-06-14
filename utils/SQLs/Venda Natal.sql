-- select * from tblmarca where marca ilike 'bic'

select 
    p.codproduto,
    p.produto || coalesce(' | ' || pv.variacao, '') as produto,
    m.marca,
    coalesce(pv.referencia, p.referencia) as referencia,
    coalesce(p.inativo, pv.descontinuado) as inativo,
    pv.dataultimacompra as comprado,
    pv.quantidadeultimacompra::bigint as quant,
    pv.custoultimacompra as custo,
    p.preco as venda,
    saldo.saldo::bigint as saldo,
    chegando.quantidade as chegando,
    --ct_venda."2015", 
    --ct_venda."2016", 
    ct_venda."2017",
    ct_venda."2018"
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
    and tblprodutobarra.codproduto in (select tblproduto.codproduto from tblproduto where tblproduto.produto ilike (''presepio%'')) -- CODIGO DA MARCA
    -- and tblprodutobarra.codproduto in (select tblproduto.codproduto from tblproduto where tblproduto.codmarca in (2)) -- CODIGO DA MARCA
    -- and tblprodutobarra.codproduto in (28580) -- CODIGO DO PRODUTO
    and extract(month from tblnegocio.lancamento) in (9, 10, 11, 12)
    and tblnegocio.lancamento >= ''2017-01-01''
    --and tblprodutobarra.codproduto = 023800
    group by
        tblprodutobarra.codprodutovariacao
        , extract(year from tblnegocio.lancamento) 
    order by 1, 2
    ', 
    'select y from generate_series(2017, 2018) y'
    ) AS ct_venda(codprodutovariacao bigint, "2017" bigint, "2018" bigint)
full join (
    select elpv.codprodutovariacao, sum(es.saldoquantidade) as saldo
    from tblestoquelocalprodutovariacao elpv
    inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
    inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao)
    inner join tblproduto p on (p.codproduto = pv.codproduto)
    where p.produto ilike 'presepio%'
    --where p.codmarca in (2) -- CODIGO DA MARCA
    --p.codproduto in (28580)
    group by elpv.codprodutovariacao
    having sum(es.saldoquantidade) != 0
    ) saldo on (saldo.codprodutovariacao = ct_venda.codprodutovariacao)
left join tblprodutovariacao pv on (pv.codprodutovariacao = coalesce(ct_venda.codprodutovariacao, saldo.codprodutovariacao))
left join tblproduto p on (p.codproduto = pv.codproduto)
    left join (
        select pb_nti.codprodutovariacao, sum(cast(coalesce(nti.qcom * coalesce(pe_nti.quantidade, 1), 0) as bigint)) as quantidade
        from tblnfeterceiro nt 
        inner join tblnfeterceiroitem nti on (nt.codnfeterceiro = nti.codnfeterceiro)
        inner join tblprodutobarra pb_nti on (pb_nti.codprodutobarra = nti.codprodutobarra)
        left join tblprodutoembalagem pe_nti on (pe_nti.codprodutoembalagem = pb_nti.codprodutoembalagem)
        where nt.codnotafiscal IS NULL
        AND (nt.indmanifestacao IS NULL OR nt.indmanifestacao NOT IN (210220, 210240))
        AND nt.indsituacao = 1
        AND nt.ignorada = FALSE
        --and pb_nti.codproduto = 24312     
        group by pb_nti.codprodutovariacao --, nt.codnfeterceiro
    ) chegando on (chegando.codprodutovariacao = pv.codprodutovariacao)
left join tblmarca m on  (m.codmarca = p.codmarca)
--where p.inativo is null
--and coalesce(saldo, 0) < coalesce(ct_venda."2017", 0)
order by 
    p.produto,
    pv.variacao


-- select * from tblmarca where marca ilike '%delta%'

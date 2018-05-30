/*

select codmarca, marca from tblmarca where controlada order by marca
update tblmarca set controlada = true where marca ilike 'pentel'

select * from tblmarca

update tblmarca set estoqueminimodias = 45, estoquemaximodias = 90 where marca ilike 'gitex'
*/

select 
    * 
    , case when (x.repor > 0) then ceil(x.repor::float / x.lote::float) * x.lote else 0 end as comprar
from 
(
    select 
        --m.marca,
        p.codproduto as "#",
        pv.codprodutovariacao as "# Var",
        p.produto,
        pv.variacao,
        --coalesce(pv.descontinuado, p.inativo) as inat_desc,
        p.produto || coalesce(' | ' || pv.variacao, '') as produto,
        coalesce(pv.referencia, p.referencia) as referencia,
        --p.preco,
        pv.custoultimacompra as custo,
        pv.dataultimacompra as data,
        chegando.quantidade as chegando,
        cast(sld.saldoquantidade as bigint) as sld,
        sld.vendadiaquantidadeprevisao as vda_dia,
        cast(case when sld.vendadiaquantidadeprevisao != 0 then sld.saldoquantidade / sld.vendadiaquantidadeprevisao else null end as bigint) as dias,
        sld.estoqueminimo as min,
        sld.estoquemaximo as max,
        coalesce((select min(pe.quantidade) from tblprodutoembalagem pe where pe.codproduto = pv.codproduto)::int, 1) as lote,
        cast(sld.estoquemaximo - (case when sld.saldoquantidade > 0 then sld.saldoquantidade else 0 end) - coalesce(chegando.quantidade, 0) as bigint) as repor
    from tblproduto p
    inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
    inner join tblmarca m on (m.codmarca = coalesce(pv.codmarca, p.codmarca))
    LEFT join (
        select 
            elpv.codprodutovariacao
            , sum(elpv.estoqueminimo) as estoqueminimo
            , sum(elpv.estoquemaximo) as estoquemaximo
            , sum(es.saldoquantidade) as saldoquantidade
            , sum(es.saldovalor) as saldovalor
            , sum(case when el.deposito then 0 else coalesce(elpv.vendadiaquantidadeprevisao, 0) end) as vendadiaquantidadeprevisao
        from tblestoquelocalprodutovariacao elpv
        inner join tblestoquelocal el on (el.codestoquelocal = elpv.codestoquelocal)
        left join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
        where el.inativo is null
        group by elpv.codprodutovariacao
        --limit 50
        ) sld on (sld.codprodutovariacao = pv.codprodutovariacao)
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
    where (m.marca ilike 'gitex')
    and pv.descontinuado is null
    and p.inativo is null
    --and p.produto ilike '%tris%'
    --and coalesce(sld.saldoquantidade, 0) < sld.estoqueminimo
    --and coalesce(sld.saldoquantidade, 0) < sld.estoquemaximo
    --and m.controlada = true
    --and pv.codprodutovariacao = 15218
    --and pv.codproduto = 24312
    order by m.marca, p.produto, pv.variacao
) x
--order by sld nulls first
--SELECT * FROM TBLPRODUTOVARIACAO WHERE CODPRODUTO = 29011

--update tblnfeterceiroitem set margem = 60 where codnfeterceiro = 17875

/*
UPDATE TBLPRODUTOVARIACAO SET DESCONTINUADO = NOW() WHERE CODPRODUTOVARIACAO IN ( NULL
,53220

)

update tblprodutovariacao set descontinuado = null where descontinuado is not null and codprodutovariacao = 84313



-- DEIXA REFERENCIA COMO MAIUSCULA

update tblproduto 
set referencia = upper(referencia)
where codmarca in (select m.codmarca from tblmarca m where m.marca ilike 'pilot')

update tblprodutovariacao 
set referencia = upper(referencia)
where codmarca in (select m.codmarca from tblmarca m where m.marca ilike 'pilot')
or codproduto in (select p.codproduto from tblproduto p inner join tblmarca m on (m.codmarca = p.codmarca) where m.marca ilike 'pilot')


*/

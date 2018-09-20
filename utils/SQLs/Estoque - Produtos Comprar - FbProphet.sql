with estoque as (
        select 
            elpv.codprodutovariacao
            , sum(es.saldoquantidade) as saldoquantidade
        from tblestoquelocalprodutovariacao elpv
        inner join tblestoquelocal el on (el.codestoquelocal = elpv.codestoquelocal)
        left join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
        where el.inativo is null
        group by elpv.codprodutovariacao
), chegando as (
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
), previsao as (
	select pvv.codprodutovariacao, sum(previsao) as previsao, sum(previsaominima) as previsaominima, sum(previsaomaxima) as previsaomaxima
	from tblprodutovariacaovenda pvv
	where pvv.data between now() and now() + '90 days'
	group by pvv.codprodutovariacao
), aulas as (
	select pvv.codprodutovariacao, sum(previsao) as previsao, sum(previsaominima) as previsaominima, sum(previsaomaxima) as previsaomaxima
	from tblprodutovariacaovenda pvv
	where pvv.data between '2019-01-01' and '2019-03-31'
	group by pvv.codprodutovariacao
)
select 
	p.codproduto
	, pv.codprodutovariacao
	, p.produto || coalesce(' ' || pv.variacao, '') as produto
	, coalesce(pv.referencia, p.referencia) as referencia
        , pv.custoultimacompra as custo
        , pv.dataultimacompra as data
        , pv.quantidadeultimacompra as compra
        , pv.vendainicio
	, estoque.saldoquantidade as estoque
	, chegando.quantidade as chegando
        , coalesce((select min(pe.quantidade) from tblprodutoembalagem pe where pe.codproduto = pv.codproduto)::int, 1) as lote	
        , previsao.previsao as previsao
        , aulas.previsao  as aulas
from tblproduto p
inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
left join estoque on (estoque.codprodutovariacao = pv.codprodutovariacao)
left join chegando on (chegando.codprodutovariacao = pv.codprodutovariacao)
left join previsao on (previsao.codprodutovariacao = pv.codprodutovariacao)
left join aulas on (aulas.codprodutovariacao = pv.codprodutovariacao)
where p.inativo is null
and pv.descontinuado is null
and p.codmarca = 197
order by p.produto, pv.variacao, p.codproduto, pv.codprodutovariacao
--where p.codproduto = 103047

--select * from tblestoquelocalprodutovariacaovenda limit 50
--select * from tblestoquelocalprodutovariacao limit 50
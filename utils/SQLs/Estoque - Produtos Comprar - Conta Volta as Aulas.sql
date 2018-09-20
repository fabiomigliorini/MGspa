with ano as (
	select elpv.codprodutovariacao, sum(elpvv.quantidade) as quantidade
	from tblestoquelocalprodutovariacao elpv
	inner join tblestoquelocalprodutovariacaovenda elpvv on (elpvv.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
	where elpvv.mes >= '2017-10-01'
	group by elpv.codprodutovariacao
), trim as (
	select elpv.codprodutovariacao, sum(elpvv.quantidade) as quantidade
	from tblestoquelocalprodutovariacao elpv
	inner join tblestoquelocalprodutovariacaovenda elpvv on (elpvv.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
	where elpvv.mes >= '2018-06-01'
	group by elpv.codprodutovariacao
), aulas_2018 as (
	select elpv.codprodutovariacao, sum(elpvv.quantidade) as quantidade
	from tblestoquelocalprodutovariacao elpv
	inner join tblestoquelocalprodutovariacaovenda elpvv on (elpvv.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
	where elpvv.mes between '2018-01-01' and '2018-03-31'
	group by elpv.codprodutovariacao
), aulas_2017 as (
	select elpv.codprodutovariacao, sum(elpvv.quantidade) as quantidade
	from tblestoquelocalprodutovariacao elpv
	inner join tblestoquelocalprodutovariacaovenda elpvv on (elpvv.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
	where elpvv.mes between '2017-01-01' and '2017-03-31'
	group by elpv.codprodutovariacao
), estoque as (
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
	, ano.quantidade as ano
	, trim.quantidade as trim
	, aulas_2018.quantidade as aulas_2018
	, aulas_2017.quantidade as aulas_2017
	, estoque.saldoquantidade as estoque
	, chegando.quantidade as chegando
        , coalesce((select max(pe.quantidade) from tblprodutoembalagem pe where pe.codproduto = pv.codproduto)::int, 1) as lote	
from tblproduto p
inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
left join ano on (pv.codprodutovariacao = ano.codprodutovariacao)
left join trim on (pv.codprodutovariacao = trim.codprodutovariacao)
left join aulas_2018 on (pv.codprodutovariacao = aulas_2018.codprodutovariacao)
left join aulas_2017 on (pv.codprodutovariacao = aulas_2017.codprodutovariacao)
left join estoque on (estoque.codprodutovariacao = pv.codprodutovariacao)
left join chegando on (chegando.codprodutovariacao = pv.codprodutovariacao)
where p.inativo is null
and pv.descontinuado is null
and p.codmarca = 138
order by p.produto, pv.variacao, p.codproduto, pv.codprodutovariacao
--where p.codproduto = 103047

--select * from tblestoquelocalprodutovariacaovenda limit 50
--select * from tblestoquelocalprodutovariacao limit 50
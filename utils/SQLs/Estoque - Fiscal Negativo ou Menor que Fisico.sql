with fisico as (
	select elpv.codestoquelocal, elpv.codprodutovariacao, em.saldoquantidade, em.saldovalor, em.customedio, es.saldoquantidade as saldoquantidade_atual, es.saldovalor as saldovalor_atual
	from tblestoquelocalprodutovariacao elpv
	inner join tblestoquelocal el on (el.codestoquelocal = elpv.codestoquelocal)
	inner join tblfilial f on (f.codfilial = el.codfilial)
	inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
	inner join tblestoquemes em on (em.codestoquemes = (select em2.codestoquemes from tblestoquemes em2 where em2.codestoquesaldo = es.codestoquesaldo and em2.mes <= '2017-12-31' order by mes desc limit 1))
	where f.codempresa = 1
), fiscal as (
	select 
		elpv.codestoquelocal, 
		elpv.codprodutovariacao, 
		em.saldoquantidade, 
		em.saldovalor, 
		em.customedio, 
		es.saldoquantidade as saldoquantidade_atual, 
		es.saldovalor as saldovalor_atual,
		em.codestoquemes
	from tblestoquelocalprodutovariacao elpv
	inner join tblestoquelocal el on (el.codestoquelocal = elpv.codestoquelocal)
	inner join tblfilial f on (f.codfilial = el.codfilial)
	inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = true)
	inner join tblestoquemes em on (em.codestoquemes = (select em2.codestoquemes from tblestoquemes em2 where em2.codestoquesaldo = es.codestoquesaldo and em2.mes <= '2017-12-31' order by mes desc limit 1))
	where f.codempresa = 1
)
select 
	n.ncm
	, p.codproduto
	, p.produto
	, pv.variacao
	, coalesce(fiscal.codestoquelocal, fisico.codestoquelocal) as codestoquelocal
	, fiscal.saldoquantidade as fiscal
	, fisico.saldoquantidade as fisico
	, fiscal.saldoquantidade_atual as fiscal_atual
	, fisico.saldoquantidade_atual as fisico_atual
	, fiscal.codestoquemes
from tblproduto p
inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
inner join tblncm n on (n.codncm = p.codncm)
left join fiscal on (fiscal.codprodutovariacao = pv.codprodutovariacao)
full join fisico on (fisico.codprodutovariacao = pv.codprodutovariacao and fisico.codestoquelocal = fiscal.codestoquelocal)
where fiscal.saldoquantidade < 0
or (fisico.saldoquantidade_atual > 0 and fiscal.saldoquantidade_atual < fisico.saldoquantidade_atual)
order by n.ncm, p.codproduto, p.codestoquelocal, p.variacao
limit 100
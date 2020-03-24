select 
	p.codproduto,
	pv.codprodutovariacao,
	p.produto,
	pv.variacao,
	el.sigla,
	(
	select em.saldoquantidade
	   from tblestoquesaldo es
          inner join tblestoquemes em on (em.codestoquesaldo = es.codestoquesaldo)
	  where es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao
	    and es.fiscal = true
	    and em.mes <= '2019-12-31'
	    order by mes desc
	    limit 1
	) as dez,
	(
	select em.saldoquantidade
	   from tblestoquesaldo es
          inner join tblestoquemes em on (em.codestoquesaldo = es.codestoquesaldo)
	  where es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao
	    and es.fiscal = true
	    and em.mes <= '2020-01-31'
	    order by mes desc
	    limit 1
	) as jan,
	(
	select em.saldoquantidade
	   from tblestoquesaldo es
          inner join tblestoquemes em on (em.codestoquesaldo = es.codestoquesaldo)
	  where es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao
	    and es.fiscal = true
	    and em.mes <= '2020-02-29'
	    order by mes desc
	    limit 1
	) as fev,
	(
	select em.saldoquantidade
	   from tblestoquesaldo es
          inner join tblestoquemes em on (em.codestoquesaldo = es.codestoquesaldo)
	  where es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao
	    and es.fiscal = true
	    and em.mes <= '2020-03-31'
	    order by mes desc
	    limit 1
	) as mar
from tblproduto p
inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
inner join tblestoquelocal el on (el.codfilial in (101, 102, 103, 104, 109))
inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao and elpv.codestoquelocal = el.codestoquelocal)
where p.codproduto = 6342
order by codfilial, variacao


--select * from tblestoquelocal
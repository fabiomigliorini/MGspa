Copy (
	select 
		p.codproduto,
		p.produto,
		pv.codprodutovariacao,
		pv.variacao,
		sp.secaoproduto,
		fp.familiaproduto,
		gp.grupoproduto,
		sgp.subgrupoproduto,
		m.marca,
		el.estoquelocal,
		elpvv.mes,
		elpvv.quantidade
	from tblproduto p
	inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
	inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao)
	inner join tblestoquelocal el on (el.codestoquelocal = elpv.codestoquelocal)
	inner join tblestoquelocalprodutovariacaovenda elpvv on (elpvv.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
	inner join tblmarca m on (m.codmarca = p.codmarca)
	inner join tblsubgrupoproduto sgp on (sgp.codsubgrupoproduto = p.codsubgrupoproduto)
	inner join tblgrupoproduto gp on (gp.codgrupoproduto = sgp.codgrupoproduto)
	inner join tblfamiliaproduto fp on (fp.codfamiliaproduto = gp.codfamiliaproduto)
	inner join tblsecaoproduto sp on (sp.codsecaoproduto = fp.codsecaoproduto)
	where elpvv.mes >= '2012-01-01'
	order by 
		p.produto, 
		p.codproduto,
		pv.variacao nulls first,
		pv.codprodutovariacao,
		el.estoquelocal,
		elpvv.mes
	--limit 100
) To '/tmp/vendas-por-variacao-local-2018-06-09.csv' 
With 
CSV 
HEADER 
DELIMITER ';' 
QUOTE '"' 
FORCE QUOTE 
	produto,
	marca,
	secaoproduto,
	familiaproduto,
	grupoproduto,
	subgrupoproduto,
	variacao,
	estoquelocal
ENCODING 'UTF-8'

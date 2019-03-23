--select * from tblestoquemovimento where round(entradaquantidade) != entradaquantidade and manual 

select 
	codestoquesaldo, 
	(select em.codestoquemes from tblestoquemes em where em.codestoquesaldo = es.codestoquesaldo and round(em.saldoquantidade) != em.saldoquantidade order by mes asc limit 1),
	es.saldoquantidade, 
	p.codproduto, 
	p.produto, 
	pv.variacao, 
	elpv.codestoquelocal,
	um.sigla
from tblestoquesaldo es
inner join tblestoquelocalprodutovariacao elpv on (elpv.codestoquelocalprodutovariacao = es.codestoquelocalprodutovariacao)
inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao)
inner join tblproduto p on (p.codproduto = pv.codproduto)
inner join tblunidademedida um on (um.codunidademedida = p.codunidademedida)
inner join tblncm n on (n.codncm = p.codncm)
where round(saldoquantidade) != saldoquantidade 
and um.sigla != 'MT'
--order by p.produto, pv.variacao, elpv.codestoquelocal
order by n.ncm ASC, p.preco ASC, p.produto ASC, elpv.codestoquelocal, pv.variacao

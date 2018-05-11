select 
    sp.secaoproduto, 
    fp.familiaproduto, 
    gp.grupoproduto, 
    sgp.subgrupoproduto, 
    m.marca, 
    p.codproduto, 
    p.produto, 
    pv.variacao, 
    elpv.codestoquelocal, 
    mes.mes, 
    sum(coalesce(mov.saidaquantidade, 0)) - sum(coalesce(mov.entradaquantidade, 0)) as venda
from tblproduto p
inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
left join tblmarca m on (m.codmarca = coalesce(pv.codmarca, p.codmarca))
inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao)
inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
inner join tblestoquemes mes on (mes.codestoquesaldo = es.codestoquesaldo)
inner join tblestoquemovimento mov on (mov.codestoquemes = mes.codestoquemes)
inner join tblsubgrupoproduto sgp on (sgp.codsubgrupoproduto = p.codsubgrupoproduto)
inner join tblgrupoproduto gp on (gp.codgrupoproduto = sgp.codgrupoproduto)
inner join tblfamiliaproduto fp on (fp.codfamiliaproduto = gp.codfamiliaproduto)
inner join tblsecaoproduto sp on (sp.codsecaoproduto = fp.codsecaoproduto)
where mov.codestoquemovimentotipo = 3001
and pv.codprodutovariacao = 61424
--and m.marca ilike 'xpto'
group by 
    sp.secaoproduto, 
    fp.familiaproduto, 
    gp.grupoproduto, 
    sgp.subgrupoproduto, 
    m.marca, 
    p.codproduto, 
    p.produto, 
    pv.variacao, 
    elpv.codestoquelocal, 
    mes.mes
order by 1, 2

-- SALDO DO ESTOQUE
select 'Estoque' as tipo, estoquelocal, m.marca, sp.secaoproduto, fp.familiaproduto, gp.grupoproduto, sgp.subgrupoproduto, p.codproduto, p.produto, p.preco, pv.variacao, es.saldoquantidade as quantidade, es.saldoquantidade * p.preco as total
from tblsecaoproduto sp
inner join tblfamiliaproduto fp on (fp.codsecaoproduto = sp.codsecaoproduto)
inner join tblgrupoproduto gp on (gp.codfamiliaproduto = fp.codfamiliaproduto)
inner join tblsubgrupoproduto sgp on (sgp.codgrupoproduto = gp.codgrupoproduto)
inner join tblproduto p on (p.codsubgrupoproduto = sgp.codsubgrupoproduto)
inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao) -- and elpv.codestoquelocal = 101001) -- Deposito
inner join tblestoquelocal el on (el.codestoquelocal = elpv.codestoquelocal)
inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false) -- Fisico
inner join tblmarca m on (m.codmarca = coalesce(pv.codmarca, p.codmarca))
--where sp.codsecaoproduto = 11
--where p.codproduto = 001624
where m.codmarca = 2
and es.saldoquantidade != 0

union all

-- Vendas
select 'Vendas' as tipo, el.estoquelocal, m.marca, sp.secaoproduto, fp.familiaproduto, gp.grupoproduto, sgp.subgrupoproduto, p.codproduto, p.produto, p.preco, pv.variacao, sum(npb.quantidade) as quantidade, sum(npb.quantidade * p.preco) as total
from tblsecaoproduto sp
inner join tblfamiliaproduto fp on (fp.codsecaoproduto = sp.codsecaoproduto)
inner join tblgrupoproduto gp on (gp.codfamiliaproduto = fp.codfamiliaproduto)
inner join tblsubgrupoproduto sgp on (sgp.codgrupoproduto = gp.codgrupoproduto)
inner join tblproduto p on (p.codsubgrupoproduto = sgp.codsubgrupoproduto)
inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
inner join tblprodutobarra pb on (pb.codprodutovariacao = pv.codprodutovariacao)
inner join tblnegocioprodutobarra npb on (npb.codprodutobarra = pb.codprodutobarra)
inner join tblnegocio n on (n.codnegocio = npb.codnegocio)
inner join tblnaturezaoperacao no on (no.codnaturezaoperacao = n.codnaturezaoperacao)
inner join tblestoquelocal el on (el.codestoquelocal = n.codestoquelocal)
inner join tblmarca m on (m.codmarca = coalesce(pv.codmarca, p.codmarca))
--where sp.codsecaoproduto = 11
--where p.codproduto = 001624
where m.codmarca = 2
and no.venda = true
and n.codnegociostatus = 2 --fechado
and n.lancamento between '2016-08-01 00:00:00.0' and '2017-03-31 23:59:59.9'
group by el.estoquelocal, m.marca, sp.secaoproduto, fp.familiaproduto, gp.grupoproduto, sgp.subgrupoproduto, p.codproduto, p.produto, p.preco, pv.variacao
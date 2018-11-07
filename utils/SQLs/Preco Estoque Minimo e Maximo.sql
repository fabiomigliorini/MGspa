select 
    p.codproduto, 
    sum(p.preco * elpv.estoqueminimo) as min_val, 
    sum(elpv.estoqueminimo) as min_qtd, 
    sum(p.preco * elpv.estoquemaximo) as max_val,
    sum(elpv.estoquemaximo) as max_qtd
from tblproduto p
inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao)
inner join tblestoquelocal el on (el.codestoquelocal = elpv.codestoquelocal)
inner join tblmarca m on (m.codmarca = p.codmarca)
where (p.inativo is not null
or el.inativo is not null)
and m.controlada = true
and m.codmarca = 173
group by p.codproduto
--limit 50

--select * from tblmarca where codmarca = 10000299

--select * from tblproduto where codproduto = 1
/*
update tblestoquelocalprodutovariacao
set estoqueminimo = null
, estoquemaximo = null
from tblprodutovariacao pv
inner join tblproduto p on (p.codproduto = pv.codproduto)
where tblestoquelocalprodutovariacao.codprodutovariacao = pv.codprodutovariacao
and p.codtipoproduto != 0
*/

update tblestoquelocalprodutovariacao
set estoqueminimo = null
, estoquemaximo = null
where codprodutovariacao in (
    select pv.codprodutovariacao 
    from tblprodutovariacao pv 
    where pv.codproduto = 301125)
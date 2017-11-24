select p.codproduto, p.produto, pv.variacao, es.saldoquantidade as quantidade, es.saldovalor as custo, es.saldoquantidade * p.preco as venda
    --sum(es.saldoquantidade) as quantidade, sum(es.saldovalor) custo, sum(es.saldoquantidade * p.preco) venda
from tblestoquelocalprodutovariacao elpv
inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao)
inner join tblproduto p on (p.codproduto = pv.codproduto)
where elpv.codestoquelocal = 103001
and es.saldoquantidade != 0
--and es.saldoquantidade > 0
order by produto
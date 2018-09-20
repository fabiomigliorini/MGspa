select m.codmarca, m.marca, elpvv.mes, sum(elpvv.valor) as valor
from tblestoquelocalprodutovariacaovenda elpvv 
inner join tblestoquelocalprodutovariacao elpv on (elpv.codestoquelocalprodutovariacao = elpvv.codestoquelocalprodutovariacao)
inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao)
inner join tblproduto p on (p.codproduto = pv.codproduto)
inner join tblmarca m on (m.codmarca = p.codmarca)
where m.controlada = true
group by m.codmarca, m.marca, elpvv.mes
--limit 50
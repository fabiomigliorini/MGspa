-- Inicializa
update tblestoquelocalprodutovariacao
set estoqueminimo = null
, estoquemaximo = null
where estoqueminimo is not null
or estoquemaximo is not null

-- Calcula Lojas
update tblestoquelocalprodutovariacao
set estoqueminimo = ceil(vendadiaquantidadeprevisao * m.estoqueminimodias) -- 15 dias
, estoquemaximo = ceil(vendadiaquantidadeprevisao * m.estoquemaximodias) -- 60 dias
from tblprodutovariacao pv
inner join tblproduto p on (p.codproduto = pv.codproduto)
inner join tblmarca m on (m.codmarca = coalesce(pv.codmarca, p.codmarca))
where tblestoquelocalprodutovariacao.codprodutovariacao = pv.codprodutovariacao
and codestoquelocal != 101001

-- Calcula Deposito pela venda das lojas
update tblestoquelocalprodutovariacao
set estoqueminimo = ceil(iq.vendadiaquantidadeprevisao * 15) -- 15 dias
, estoquemaximo = ceil(iq.vendadiaquantidadeprevisao * 60) -- 60 dias
from (
	select elpv_iq.codprodutovariacao, sum(coalesce(elpv_iq.vendadiaquantidadeprevisao, 0)) as vendadiaquantidadeprevisao
	from tblestoquelocalprodutovariacao elpv_iq
	where elpv_iq.codestoquelocal != 101001 -- deposito
	group by elpv_iq.codprodutovariacao
	) iq
where tblestoquelocalprodutovariacao.codprodutovariacao = iq.codprodutovariacao
and tblestoquelocalprodutovariacao.codestoquelocal = 101001

-- Coloca estoque maximo como dobro do minimo quando maximo igual a minimo
update tblestoquelocalprodutovariacao
set estoquemaximo = estoqueminimo * 2
where estoquemaximo <= estoqueminimo


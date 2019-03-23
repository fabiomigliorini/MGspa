with saldos as (
	select 
		p.codproduto, 
		p.produto, 
		pv.variacao, 
		el.sigla, 
		es.saldoquantidade, 
		es.saldovalor, 
		(select max(npb.criacao) from tblnegocioprodutobarra npb inner join tblprodutobarra pb on (pb.codprodutobarra = npb.codprodutobarra) where pb.codproduto = p.codproduto) as utilizacao
	from tblproduto p
	inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
	inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao)
	inner join tblestoquelocal el on (el.codestoquelocal = elpv.codestoquelocal)
	inner join tblfilial f on (f.codfilial = el.codfilial)
	inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = true)
	where f.codempresa = 1
	and es.saldoquantidade != 0 
)
select * 
from saldos s
where coalesce(s.utilizacao, '2000-01-01'::date) <= '2016-12-31 23:59'
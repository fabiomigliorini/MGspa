select 
	p.codproduto
	, p.produto
	, pv.variacao
	, es.saldoquantidade
	, cast(es.dataentrada as date) entrada
	, cast(es.ultimaconferencia as date) conferencia
	, (
		select string_agg(barras, ', ')
		from tblprodutobarra pb
		left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
		where pb.codprodutovariacao = pv.codprodutovariacao
		group by codprodutovariacao
	) as barras
from tblproduto p
inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao and elpv.codestoquelocal = 103001) -- CENTRO
inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
where coalesce(pv.codmarca, p.codmarca) = 29 -- ACRILEX
and es.saldoquantidade != 0
and (ultimaconferencia < '2017-07-01' or ultimaconferencia is null)
order by p.produto, pv.variacao

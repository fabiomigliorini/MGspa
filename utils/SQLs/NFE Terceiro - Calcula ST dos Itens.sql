with final as (
	with itens as (
		select 
			nti.nitem,
			nti.cprod, 
			nti.xprod, 
			--nti.cean, 
			case when nti.ncm = n.ncm then n.ncm else 'DIVERG' end as ncm,
			case when coalesce(nti.cest, '') = coalesce(c.cest, '') then c.cest else 'DIVERG' end as cest,
			c.mva,
			coalesce(vprod, 0) + coalesce(vfrete, 0) + coalesce(vseg, 0) + coalesce(voutro, 0) - coalesce(vdesc, 0) as valor,
			ipivipi,
			case when coalesce(picms, 0) > 7 then (coalesce(vprod, 0) + coalesce(vfrete, 0) + coalesce(vseg, 0) + coalesce(voutro, 0) - coalesce(vdesc, 0)) * 0.07 else vicms end as vicms,
			vicmsst
		from tblnfeterceiroitem nti
		left join tblprodutobarra pb on (pb.codprodutobarra = nti.codprodutobarra)
		left join tblproduto p on (p.codproduto = pb.codproduto)
		left join tblncm n on (n.codncm = p.codncm)
		left join tblcest c on (c.codcest = p.codcest)
		where nti.codnfeterceiro = 27355
		order by nitem
	)
	select 
		*,
		round((((valor + coalesce(ipivipi, 0)) * (1+(mva/100))) * 0.17) - vicms, 2) as vicmsstcalculado
	from itens
)
select 
	*,
	vicmsstcalculado - coalesce(vicmsst, 0) as diferenca,
	sum(vicmsstcalculado) over (order by nitem asc) as acumulado
from final
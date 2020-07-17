with final as (
	with itens as (
		select
			nti.nitem,
			nti.cprod,
			nti.xprod,
			--nti.cean,
			case when nti.ncm = n.ncm then n.ncm else 'DIVERG' end as ncm,
			case when coalesce(nti.cest, '') = coalesce(c.cest, '') then c.cest else 'DIVERG' end as cest,
			round(1 + (c.mva / 100), 4) as mva,
			coalesce(vprod, 0) + coalesce(vfrete, 0) + coalesce(vseg, 0) + coalesce(voutro, 0) + coalesce(ipivipi, 0) - coalesce(vdesc, 0) as valor,
			case when coalesce(n.bit, false) then
				0.4117
			else
				1.0
			end as reducao,
			case when coalesce(picms, 0) > 7 then
				(coalesce(vprod, 0) + coalesce(vfrete, 0) + coalesce(vseg, 0) + coalesce(voutro, 0) - coalesce(vdesc, 0)) * 0.07
			else
				case when coalesce(vicms, 0) = 0 then
					case when p.importado then
						(coalesce(vprod, 0) + coalesce(vfrete, 0) + coalesce(vseg, 0) + coalesce(voutro, 0) - coalesce(vdesc, 0)) * 0.04
					else 
						(coalesce(vprod, 0) + coalesce(vfrete, 0) + coalesce(vseg, 0) + coalesce(voutro, 0) - coalesce(vdesc, 0)) * 0.07
					end
				else 
					coalesce(vicms, 0)
				end
			end as vicms,
			vicmsst
		from tblnfeterceiroitem nti
		left join tblprodutobarra pb on (pb.codprodutobarra = nti.codprodutobarra)
		left join tblproduto p on (p.codproduto = pb.codproduto)
		left join tblncm n on (n.codncm = p.codncm)
		left join tblcest c on (c.codcest = p.codcest)
		where nti.codnfeterceiro = 28996
		order by nitem
	)
	select
		*,
		round((valor * reducao * mva * 0.17) - (vicms * reducao), 2) as vicmsstcalculado
	from itens
)
select
	*,
	vicmsstcalculado - coalesce(vicmsst, 0) as diferenca,
	sum(vicmsstcalculado - coalesce(vicmsst, 0)) over (order by nitem asc) as acumulado
from final
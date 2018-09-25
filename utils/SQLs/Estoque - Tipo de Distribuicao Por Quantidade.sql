select 
	m.marca
	, p.codproduto
	, pv.codprodutovariacao
	, p.produto || coalesce(' | ' || pv.variacao, '') as produto
	, pv.estoqueminimo
	, pv.estoquemaximo
	, case when (coalesce(pv.estoquemaximo, 0) >= 200) then '70% Deposito, 30% Lojas' else 
		case when (coalesce(pv.estoquemaximo, 0) >= 100) then '50% Deposito, 50% Lojas' else 
			case when (coalesce(pv.estoquemaximo, 0) > 30) then '20% Deposito, 80% Lojas' else 'Nada no Depósito' 
			end
		end
	  end as Distr
from tblproduto p
inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
inner join tblmarca m on (m.codmarca = p.codmarca)
where p.inativo is null
and pv.inativo is null
and pv.descontinuado is null
and m.controlada = true
--and coalesce(pv.estoquemaximo, 0) > 30
--and m.marca ilike '%maxprint%'
--and p.produto ilike 'bobina%'
--order by pv.estoquemaximo desc
order by m.marca, p.produto, p.codproduto, pv.variacao, pv.codprodutovariacao desc
--limit 100
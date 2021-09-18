with totais as (
	select 
		p.codpessoa,
		p.fantasia, 
		sum(t.credito) as credito, 
		sum(case when (t.saldo < 0) then t.saldo else 0 end * -1) as pagar,
		sum(case when (t.saldo > 0) then t.saldo else 0 end) as adiantamento
	from tbltitulo t 
	inner join tblpessoa p on (p.codpessoa = t.codpessoa)
	where t.codcontacontabil = 158
	and t.estornado is null
	--and t.emissao  <= '2020-12-31 23:59:59'
	group by p.codpessoa, p.fantasia 
) 
select 
	totais.codpessoa,
	totais.fantasia,
	totais.credito, 
	totais.adiantamento,
	totais.credito + totais.adiantamento as total,
	totais.pagar
from totais	
order by totais.credito + totais.adiantamento desc

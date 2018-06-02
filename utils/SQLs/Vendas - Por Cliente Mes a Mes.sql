with vendas as (
	select 
		n.codpessoa, 
		sum(case when extract(year from lancamento) = 2012 then n.valortotal * case when n.codoperacao = 1 then -1 else 1 end else null end) as "2012",
		sum(case when extract(year from lancamento) = 2013 then n.valortotal * case when n.codoperacao = 1 then -1 else 1 end else null end) as "2013",
		sum(case when extract(year from lancamento) = 2014 then n.valortotal * case when n.codoperacao = 1 then -1 else 1 end else null end) as "2014",
		sum(case when extract(year from lancamento) = 2015 then n.valortotal * case when n.codoperacao = 1 then -1 else 1 end else null end) as "2015",
		sum(case when extract(year from lancamento) = 2016 then n.valortotal * case when n.codoperacao = 1 then -1 else 1 end else null end) as "2016",
		sum(case when extract(year from lancamento) = 2017 then n.valortotal * case when n.codoperacao = 1 then -1 else 1 end else null end) as "2017",
		sum(case when extract(year from lancamento) = 2018 then n.valortotal * case when n.codoperacao = 1 then -1 else 1 end else null end) as "2018",
		sum(case when extract(year from lancamento) = 2018 and extract(month from lancamento) = 1 then n.valortotal * case when n.codoperacao = 1 then -1 else 1 end else null end) as "Jan",
		sum(case when extract(year from lancamento) = 2018 and extract(month from lancamento) = 2 then n.valortotal * case when n.codoperacao = 1 then -1 else 1 end else null end) as "Fev",
		sum(case when extract(year from lancamento) = 2018 and extract(month from lancamento) = 3 then n.valortotal * case when n.codoperacao = 1 then -1 else 1 end else null end) as "Mar",
		sum(case when extract(year from lancamento) = 2018 and extract(month from lancamento) = 4 then n.valortotal * case when n.codoperacao = 1 then -1 else 1 end else null end) as "Abr",
		sum(case when extract(year from lancamento) = 2018 and extract(month from lancamento) = 5 then n.valortotal * case when n.codoperacao = 1 then -1 else 1 end else null end) as "Mai",
		sum(case when extract(year from lancamento) = 2018 and extract(month from lancamento) = 6 then n.valortotal * case when n.codoperacao = 1 then -1 else 1 end else null end) as "Jun"
	from tblnegocio n
	inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = n.codnaturezaoperacao)
	where n.codpessoa != 1
	and n.lancamento >= '2012-01-01'
	and n.codnegociostatus = 2
	and nat.venda = true
	group by n.codpessoa
) 
select 
	p.fantasia,
	c.cidade, 
	p.telefone1, 
	p.contato, 
	v.*
from vendas v
inner join tblpessoa p on (p.codpessoa = v.codpessoa)
inner join tblcidade c on (c.codcidade = p.codcidade)


/*
select * 
from tblpessoa
i
*/
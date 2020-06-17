-- POR MES
select 
	-- POR MES
	 date_trunc('month', lancamento) as mes
	, filial
	--, pv.fantasia fantasiavendedor
	--, p.fantasia
	--, gc.grupocliente
	, sum(n.valortotal * case when no.codoperacao = 1 then -1 else 1 end ) as venda
	, sum(case when no.codoperacao = 1 then -1 else 1 end ) as quant
from tblnegocio n
inner join tblfilial f on (f.codfilial = n.codfilial)
left join tblpessoa pv on (pv.codpessoa = n.codpessoavendedor)
left join tblpessoa p on (p.codpessoa = n.codpessoa)
left join tblgrupocliente gc on (gc.codgrupocliente = p.codgrupocliente)
inner join tblnaturezaoperacao no on (no.codnaturezaoperacao = n.codnaturezaoperacao)
where codnegociostatus = 2
--and lancamento <= '2018-01-25 23:59:59.9'
and lancamento >= '2012-01-01 00:00:00.0'
and lancamento <= '2020-05-31 23:59:59.9'
and n.codnaturezaoperacao in (1, 2, 5) -- venda, devolucao, cupom
--and n.codnaturezaoperacao in (2) -- venda, devolucao, cupom
--and n.codfilial in (101, 102) -- Botanico
--and n.codfilial in (301, 103) -- Centro
--and n.codfilial in (201, 104, 202) -- Imperial
and n.codpessoa not in (select tblfilial.codpessoa from tblfilial)
group by 
	-- POR MES
	  date_trunc('month', lancamento)
	, filial
	--, fantasiavendedor
	--, p.fantasia
	--, gc.grupocliente
order by 1 desc, 2 desc
--limit 200
--"2015-01-12 00:00:00";263437.47

/*
-- POR SEMANA
select 
	-- POR SEMANA
	extract('isoyear' from lancamento) as ano
	, extract('week' from lancamento) as semana
	--, pv.fantasia fantasiavendedor
	--, p.fantasia
	--, gc.grupocliente
	, sum(n.valortotal * case when no.codoperacao = 1 then -1 else 1 end ) as venda
from tblnegocio n
inner join tblfilial f on (f.codfilial = n.codfilial)
left join tblpessoa pv on (pv.codpessoa = n.codpessoavendedor)
left join tblpessoa p on (p.codpessoa = n.codpessoa)
left join tblgrupocliente gc on (gc.codgrupocliente = p.codgrupocliente)
inner join tblnaturezaoperacao no on (no.codnaturezaoperacao = n.codnaturezaoperacao)
where codnegociostatus = 2
--and lancamento <= '2018-01-25 23:59:59.9'
and lancamento >= '2012-01-01 00:00:00.0'
and lancamento <= '2019-03-31 23:59:59.9'
and n.codnaturezaoperacao in (1, 2, 5) -- venda, devolucao, cupom
--and n.codnaturezaoperacao in (2) -- venda, devolucao, cupom
--and n.codfilial in (101, 102) -- Botanico
--and n.codfilial in (301, 103) -- Centro
--and n.codfilial in (201, 104, 202) -- Imperial
and n.codpessoa not in (select tblfilial.codpessoa from tblfilial)
group by 
	-- POR SEMANA
	extract('isoyear' from lancamento) 
	, extract('week' from lancamento) 
	--, fantasiavendedor
	--, p.fantasia
	--, gc.grupocliente
order by 1 desc, 2 desc
--limit 200
--"2015-01-12 00:00:00";263437.47

*/
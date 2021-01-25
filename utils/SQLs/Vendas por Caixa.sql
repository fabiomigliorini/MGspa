select
	date_trunc('month', n.lancamento) as mes,
	n.codfilial,
	u.usuario,
	--sum(n.valortotal) as valortotal,
	count(npb.codnegocioprodutobarra) as quantidade --*
from tblnegocio n
inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = n.codnaturezaoperacao)
inner join tblusuario u on (u.codusuario = n.codusuario)
inner join tblnegocioprodutobarra npb on (npb.codnegocio = n.codnegocio)
where n.codnegociostatus = 2
and n.lancamento between '2020-10-01' and '2020-10-31 23:59:59'
and nat.venda = 1
and n.codfilial = 103
group by
	date_trunc('month', n.lancamento),
	n.codfilial,
	u.usuario
order by 4 desc
--limit 50

--group by date_trunc('month', n.lancamento)

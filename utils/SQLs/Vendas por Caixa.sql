select
	date_trunc('month', n.lancamento) as mes,
	f.filial,
	u.usuario,
	--sum(npb.valortotal) as valortotal,
	count(npb.codnegocioprodutobarra) as quantidade --*
from tblnegocio n
inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = n.codnaturezaoperacao)
inner join tblusuario u on (u.codusuario = n.codusuario)
inner join tblnegocioprodutobarra npb on (npb.codnegocio = n.codnegocio)
inner join tblfilial f ON (f.codfilial = n.codfilial)
where n.codnegociostatus = 2
and n.lancamento between '2022-02-01' and '2022-02-28 23:59:59'
and nat.venda = 1
--and n.codfilial = 102
group by
	date_trunc('month', n.lancamento),
	f.filial,
	u.usuario
order by 4 desc
--limit 50

--group by date_trunc('month', n.lancamento)


update tblnegocio set codnegociostatus = 1 where codnegocio = 2258445

update tblnfeterceiroitem set nitem = nitem * 100 where codnfeterceiro  = 34550

update tblnfeterceiroitem set margem = 37 where codnfeterceiro  = 34550

select nextval('tblnfeterceiroitem_codnfeterceiroitem_seq')


update tblmarca set controlada = true where marca ilike 'santana' 


select criacao, cnpj, * from tblpessoa where codusuariocriacao is null order by criacao desc nulls last










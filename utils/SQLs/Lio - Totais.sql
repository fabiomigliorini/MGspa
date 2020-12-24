select 
	f.filial,	
	t.lioterminal,
	date_trunc('DAY', p.criacao) as dia,
	u.usuario,
	count(*), 
	sum(p.valorpago) 
from tblliopedido p
left join tblliopedidopagamento pp on (pp.codliopedido = p.codliopedido)
left join tbllioterminal t on (t.codlioterminal = pp.codlioterminal)
left join tblfilial f on (f.codfilial = t.codfilial)
left join tblusuario u on (u.codusuario = p.codusuariocriacao)
where p.criacao between '2020-11-30 00:00:00' and '2020-12-19 23:59:59.9' 
and p.valorpago > 0 
group by 
	f.filial,
	t.lioterminal,
	u.usuario,
	date_trunc('DAY', p.criacao)
order by 1, 2, 3

select * 
from tblliopedido t 
where criacao >= '2020-11-30' and codusuariocriacao = 302012 order by criacao desc


select * 
from tblliopedido p 
left join tblnegocioformapagamento nfp on (nfp.codliopedido = p.codliopedido)
where nfp.codnegocioformapagamento is null

select 
	f.filial,	
	t.lioterminal,
	date_trunc('DAY', p.criacao) as dia,
	u.usuario,
	count(*), 
	sum(p.valorpago) 
from tblliopedido p
left join tblliopedidopagamento pp on (pp.codliopedido = p.codliopedido)
left join tbllioterminal t on (t.codlioterminal = pp.codlioterminal)
left join tblfilial f on (f.codfilial = t.codfilial)
left join tblusuario u on (u.codusuario = p.codusuariocriacao)
where p.criacao between '2020-12-19 00:00:00' and '2020-12-19 23:59:59.9' 
and p.valorpago > 0
and t.lioterminal = '00686052'
group by 
	f.filial,
	t.lioterminal,
	u.usuario,
	date_trunc('DAY', p.criacao)
order by 1, 2, 3
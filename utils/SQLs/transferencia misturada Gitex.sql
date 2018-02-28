with cen as (
	select pb_c.codprodutobarra, sum(npb_c.quantidade) as quantidade
	from tblnegocioprodutobarra npb_c
	inner join tblnegocio n_c on (n_c.codnegocio = npb_c.codnegocio)
	inner join tblprodutobarra pb_c on (pb_c.codprodutobarra = npb_c.codprodutobarra)
	inner join tblproduto p_c on (p_c.codproduto = pb_c.codproduto)
	where n_c.codnegociostatus = 2
	and n_c.lancamento between '2018-02-05' and '2018-02-05 23:59:59'
	and n_c.codnaturezaoperacao = 15 --transf saida
	and n_c.codfilial = 101
	and n_c.codpessoa = 3556
	and p_c.codmarca = 10000148
	group by pb_c.codprodutobarra	
), bot as (
	select pb_b.codprodutobarra, sum(npb_b.quantidade) as quantidade
	from tblnegocioprodutobarra npb_b
	inner join tblnegocio n_b on (n_b.codnegocio = npb_b.codnegocio)
	inner join tblprodutobarra pb_b on (pb_b.codprodutobarra = npb_b.codprodutobarra)
	inner join tblproduto p_b on (p_b.codproduto = pb_b.codproduto)
	where n_b.codnegociostatus = 2
	and n_b.lancamento between '2018-02-05' and '2018-02-05 23:59:59'
	and n_b.codnaturezaoperacao = 15 --transf saida
	and n_b.codfilial = 101
	and n_b.codpessoa = 3508
	and p_b.codmarca = 10000148
	and n_b.codnegocio <> 1004741
	group by pb_b.codprodutobarra
), def as (
	select npb.codprodutobarra, sum(npb.quantidade) as quantidade
	from tblnegocioprodutobarra npb
	where npb.codnegocio = 1004741
	group by npb.codprodutobarra
)
select def.*, bot.*, cen.*
from def
full outer join bot on (bot.codprodutobarra = def.codprodutobarra)
full outer join cen on (cen.codprodutobarra = def.codprodutobarra)
--where coalesce(def.quantidade, 0) != coalesce(bot.quantidade, 0) + coalesce(cen.quantidade, 0)

/*
select * 
from tblnegocioprodutobarra npb
where npb.codnegocio = 1004741
*/
-- transacoes para refazer consulta
select 
	codnegocio, 
	codstonepretransacao, 
	'curl https://api.mgspa.mgpapelaria.com.br/api/v1/stone-connect/pre-transacao/' || codstonepretransacao, 
	criacao,
	* 
from tblstonepretransacao t 
where not processada 
and ativa
order by criacao desc

select count(*)
from tblstonepretransacao t 
where not processada 
and ativa

select * from tblstonefilial t 

-- fechado a vista e a prazo
with c as (
	select spt.codnegocio, st.status, sum(st.valor) as valorcartao  
	from tblstonepretransacao spt
	inner join tblstonetransacao st on (st.codstonepretransacao = spt.codstonepretransacao)
	where st.status = 1
	group by spt.codnegocio, st.status
),
p as (
	select nfp.codnegocio, sum(nfp.valorpagamento) as valorpagamento 
	from tblnegocioformapagamento nfp 
	where nfp.codformapagamento not in (00001010, 00005605, 00002010, 00001030) --Dinheiro, Stone, Cartao, vale
	group by nfp.codnegocio
)
select * 
from tblnegocio n
inner join p on (p.codnegocio = n.codnegocio)
inner join c on (c.codnegocio = n.codnegocio)
where n.codnegociostatus = 2
and coalesce(c.valorcartao, 0) + coalesce(p.valorpagamento, 0) > coalesce(n.valortotal, 0)
--and n.codnegocio = 2381383

select * from tblnegocioformapagamento t where codnegocio = 2449702

select * from tblnegocio t where codnegocio = 2449361


/*

-- wget https://api.mgspa.mgpapelaria.com.br/api/v1/stone-connect/pre-transacao/11

select * from tblstonepretransacao t where codnegocio = 2381383 -- 2376451

select * from tblstonetransacao t where codstonepretransacao = 925 --52378

select * from tblnegocioformapagamento t where codnegocio = 2381383

update tbltitulo set codnegocioformapagamento = null where codnegocioformapagamento = 2405990

delete from tblnegocioformapagamento where codnegocioformapagamento = 2405990

*/

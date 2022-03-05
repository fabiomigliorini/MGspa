-- Busta Transferencias registradas como Vendas
update tblnegocio 
set codnaturezaoperacao  = 15 -- transferencia de saida
where tblnegocio.codnegocio in (
	select n.codnegocio
	from tblnegocio n
	inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = n.codnaturezaoperacao)
	inner join tblfilial orig on (orig.codfilial = n.codfilial)
	inner join tblfilial dest on (dest.codpessoa = n.codpessoa)
	where nat.venda = true 
	and orig.codempresa = dest.codempresa 
	and orig.codfilial != dest.codfilial
	and n.codnegociostatus = 2
	--and n.codnegocio = 2419141
)

-- Verifica movimentacao de estoque que ficou errada
select distinct 'wget https://sistema.mgpapelaria.com.br/MGLara/estoque/gera-movimento-negocio/' || n.codnegocio
from tblnegocio n
inner join tblnegocioprodutobarra npb on (npb.codnegocio = n.codnegocio)
inner join tblestoquemovimento em on (em.codnegocioprodutobarra = npb.codnegocioprodutobarra)
where n.codnaturezaoperacao = 15 -- transferencia de saida
and em.codestoquemovimentotipo not in (4101, 4201) 

-- Altera Tipo do Titulo
update tbltitulo set codtipotitulo = 922 where tbltitulo.codtitulo in (
	select t.codtitulo
	from tblnegocio n
	inner join tblnegocioformapagamento nfp on (nfp.codnegocio = n.codnegocio)
	inner join tbltitulo t on (t.codnegocioformapagamento = nfp.codnegocioformapagamento)
	where n.codnaturezaoperacao = 15
	and t.codtipotitulo != 922
)

-- Altera Conta Contabil do Titulo
update tbltitulo set codcontacontabil = 14 where tbltitulo.codtitulo in (
	select t.codtitulo
	from tblnegocio n
	inner join tblnegocioformapagamento nfp on (nfp.codnegocio = n.codnegocio)
	inner join tbltitulo t on (t.codnegocioformapagamento = nfp.codnegocioformapagamento)
	where n.codnaturezaoperacao = 15
	and t.codcontacontabil != 14
)

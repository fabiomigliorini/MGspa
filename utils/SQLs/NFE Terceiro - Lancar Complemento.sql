--STYROFORM (1/2 mais Frete)
--update tblnfeterceiroitem set complemento = (vprod *1.8933) , margem = 37 where codnfeterceiro = 26000

--CIRANDA TEXTIL / BRITANNIA (Nota Cheia, mas quantidade dos produtos é / 100)
--update tblnfeterceiroitem set qcom = (qcom/10), qtrib = (qtrib/10), xprod = xprod || ' C/10 *' where cprod ilike '1314/%' and codnfeterceiro = 26044
--update tblnfeterceiroitem set qcom = (qcom/10), qtrib = (qtrib/10), xprod = xprod || ' C/10 *' where cprod ilike '4810/%' and codnfeterceiro = 26044
--update tblnfeterceiroitem set qcom = (qcom/10), qtrib = (qtrib/10), xprod = xprod || ' C/10 *' where cprod ilike '4833/%' and codnfeterceiro = 26044
--update tblnfeterceiroitem set qcom = (qcom/10), qtrib = (qtrib/10), xprod = xprod || ' C/10 *' where cprod ilike '7239/%' and codnfeterceiro = 26044
--update tblnfeterceiroitem set qcom = (qcom/10), qtrib = (qtrib/10), xprod = xprod || ' C/10 *' where cprod ilike '7240/%' and codnfeterceiro = 26044
--update tblnfeterceiroitem set qcom = (qcom/10), qtrib = (qtrib/10), xprod = xprod || ' C/10 *' where cprod ilike '4826/%' and codnfeterceiro = 26044
--update tblnfeterceiroitem set qcom = (qcom/100), qtrib = (qtrib/100), xprod = xprod || ' C/100 *' where cprod ilike '0916/%' and codnfeterceiro = 26044
--update tblnfeterceiroitem set vuntrib = vprod/qtrib, vuncom = vprod/qcom where codnfeterceiro = 26044

--ISSAM/ZEIN
-- update tblnfeterceiroitem set complemento = (vprod / 0.35) - vprod, margem = 40 where codnfeterceiro = 29140
-- update tblnfeterceiroitem set complemento = vprod, margem = 40 where codnfeterceiro = 30044

--FOUR STAR / CW
update tblnfeterceiroitem set complemento = (vprod * 4 * 0.9) - vprod, margem = 40 where codnfeterceiro in (37561, 37565)

update tblnfeterceiroitem  set complemento = -4.8 where codnfeterceiroitem = 347981

--Lua de Cristal
--update tblnfeterceiroitem set complemento = vprod, margem = 37 where codnfeterceiro in (26042)

--Elite / Imposul
--update tblnfeterceiroitem set complemento = vprod, margem = 40 where codnfeterceiro in (34769)

--Lumasol
--update tblnfeterceiroitem set complemento = vprod, margem = 37 where codnfeterceiro in (27605)


update tblnfeterceiroitem set complemento = (vprod * 0.934308075), margem = 37 where codnfeterceiro in (32388)

select sum(vprod) from tblnfeterceiroitem t where codnfeterceiro in (32388)

--Republic VIX
--update tblnfeterceiroitem set complemento = vprod, margem = 37 where codnfeterceiro in (25002)

--99 Express
--update tblnfeterceiroitem set complemento = vprod - ipivipi, margem = 75 where codnfeterceiro in (24568)

--MAGNO
--update tblnfeterceiroitem set complemento = vprod * 0.98630137, margem = 37 where codnfeterceiro in (25681)

--DU Careca
--update tblnfeterceiroitem set complemento = vprod - vdesc, margem = 37 where codnfeterceiro in (27610)

-- Rocie / Fartex / Wincy / Rio de Ouro
update tblnfeterceiroitem set complemento = (vprod * 2.5) - vprod, margem = 40 where codnfeterceiro in (36141)


-- Bazzi Company
-- update tblnfeterceiroitem set complemento = (vprod * 5.2435) - vprod, margem = 40 where codnfeterceiro in (29286)

-- Brindes Coelho (Ex Lumasol)
-- update tblnfeterceiroitem set complemento = (vprod * 1.014355021), margem = 40 where codnfeterceiro in (29823) 

-- Brindes Coelho (Ex Lumasol)
-- update tblnfeterceiroitem set complemento = (vprod * 1.014355021), margem = 40 where codnfeterceiro in (30001) 

update tblnfeterceiroitem set complemento = (vprod * 1.5), margem = 40 where codnfeterceiro in (33950)

update tblnfeterceiroitem set complemento = vprod, margem = 37 where codnfeterceiro in (35948)

update tblnfeterceiroitem set complemento = vprod * 2.02302837016504, margem = 40 where codnfeterceiro in (34983)

update tblnfeterceiroitem set complemento = null, margem = 40 where codnfeterceiro in (34983)

update tblnfeterceiroitem set complemento = (vprod * 2), margem = 40 where codnfeterceiro in (35902)

-- Multi Placas
update tblnfeterceiroitem set complemento = (vprod * 9), margem = 40 where codnfeterceiro in (361592107)


with it as (
	select nti.codnfeterceiro, sum(nti.complemento) as complemento
	from tblnfeterceiroitem nti
	group by nti.codnfeterceiro
)
select nt.valorprodutos, nt.valortotal, it.complemento, nt.valortotal + it.complemento as geral
from tblnfeterceiro nt
inner join it on (it.codnfeterceiro  = nt.codnfeterceiro)
where nt.codnfeterceiro in (30171)


--select * from tblnfeterceiroitem where codnfeterceiro = 24692 order by nitem

update tblnfeterceiroitem set complemento = vprod where codnfeterceiro = 36549


select * from tblnfeterceiroitem nti where codnfeterceiro = 36343


select * from tblnfeterceiroitem   where codnfeterceiroitem = 389620



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
--update tblnfeterceiroitem set complemento = (vprod / 0.35) - vprod, margem = 100 where codnfeterceiro = 24454

--WINCY
--update tblnfeterceiroitem set complemento = (vprod * 1.5), margem = 40 where codnfeterceiro = 24521
--update tblnfeterceiroitem set complemento = (vprod * 1.4), margem = 30 where codnfeterceiro = 25931

--FOUR STAR / CW
update tblnfeterceiroitem set complemento = (vprod * 4 * 0.9) - vprod, margem = 40 where codnfeterceiro in (27133)

--99 Express
--update tblnfeterceiroitem set complemento = vprod - ipivipi, margem = 75 where codnfeterceiro in (24568)

--MAGNO
--update tblnfeterceiroitem set complemento = vprod * 0.98630137, margem = 37 where codnfeterceiro in (25681)



with it as (
	select nti.codnfeterceiro, sum(nti.complemento) as complemento
	from tblnfeterceiroitem nti
	group by nti.codnfeterceiro
)
select nt.valorprodutos, nt.valortotal, it.complemento, nt.valortotal + it.complemento as geral
from tblnfeterceiro nt
inner join it on (it.codnfeterceiro  = nt.codnfeterceiro)
where nt.codnfeterceiro in (25499)


--select * from tblnfeterceiroitem where codnfeterceiro = 24692 order by nitem
--ISSAM/ZEIN
--update tblnfeterceiroitem set complemento = (vprod / 0.35) - vprod, margem = 100 where codnfeterceiro = 24454

--WINCY
--update tblnfeterceiroitem set complemento = (vprod * 1.5), margem = 100 where codnfeterceiro = 24521

--FOUR STAR / CW
--update tblnfeterceiroitem set complemento = (vprod * 4 * 0.9) - vprod, margem = 120 where codnfeterceiro in (24812, 24811)

--99 Express
--update tblnfeterceiroitem set complemento = vprod - ipivipi, margem = 75 where codnfeterceiro in (24568)

with it as (
	select nti.codnfeterceiro, sum(nti.complemento) as complemento
	from tblnfeterceiroitem nti
	group by nti.codnfeterceiro
)
select nt.valorprodutos, nt.valortotal, it.complemento, nt.valortotal + it.complemento as geral
from tblnfeterceiro nt
inner join it on (it.codnfeterceiro  = nt.codnfeterceiro)
where nt.codnfeterceiro in (24568)


--select * from tblnfeterceiroitem where codnfeterceiro = 24692 order by nitem
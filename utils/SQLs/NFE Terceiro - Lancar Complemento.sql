--update tblnfeterceiroitem set complemento = vprod * 2.6, margem = 85 where codnfeterceiro = 22721

with it as (
	select nti.codnfeterceiro, sum(nti.complemento) as complemento
	from tblnfeterceiroitem nti
	group by nti.codnfeterceiro
)
select nt.valorprodutos, nt.valortotal, it.complemento, nt.valortotal + it.complemento as geral
from tblnfeterceiro nt
inner join it on (it.codnfeterceiro  = nt.codnfeterceiro)
where nt.codnfeterceiro = 22721
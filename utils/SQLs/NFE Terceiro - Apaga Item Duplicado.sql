delete from tblnfeterceiroitem 
where codnfeterceiroitem in (
	select max(codnfeterceiroitem)
		--, nitem, cprod, cean, count(*)
	from tblnfeterceiroitem nti
	where nti.codnfeterceiro = 26140
	group by nitem, cprod, cean
	having count(*) > 1
)
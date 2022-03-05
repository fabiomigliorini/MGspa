with ult as (
	select t.codfilial, max(t.nsu) as ultnsu
	from tbldistribuicaodfe t
	group by t.codfilial 
)
select *
from tbldistribuicaodfe dd
inner join ult on (ult.codfilial = dd.codfilial and dd.nsu != ult.ultnsu)
left join tbldistribuicaodfe dd_prox on (dd.codfilial = dd_prox.codfilial and (dd.nsu + 1) = dd_prox.nsu)
where dd_prox.coddistribuicaodfe is null

select *
from tbldistribuicaodfe dd 
where codfilial = 401
order by nsu desc

select * from tblpessoa where codpessoa = 12410

select * from tblnfeterceiro t where codnfeterceiro = 37395

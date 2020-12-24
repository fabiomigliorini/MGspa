select criacao, * from tblnfeterceiroitem where '7899755643463' in (cean, ceantrib) order by criacao desc nulls last

select * from tblproduto where codusuariocriacao  = 1 and criacao >= '2020-12-02' and produto  ilike '@ %'

/*
select * from tblnfeterceiroitem where cprod ilike '%PG525%'
*/s

--select * from tblnegocioprodutobarra where codprodutobarra = 30014848
select criacao, * from tblnfeterceiroitem where '7891169081176' in (cean, ceantrib) order by criacao desc nulls last

select * from tblproduto where codusuariocriacao  = 1 and criacao >= '2020-12-02' and produto  ilike '@ %'

/*
select * from tblnfeterceiroitem where cprod ilike '%PG525%'
*/s

--select * from tblnegocioprodutobarra where codprodutobarra = 30014848


update tblnegocioprodutobarra 
set codprodutobarra = 986198
where tblnegocioprodutobarra.codnegocio in (
	select n.codnegocio
	from tblnegocio n 
	where n.codnaturezaoperacao  = 00000004
	and n.codpessoa = 30011016
	and n.lancamento >= '2019-08-01'
)
and tblnegocioprodutobarra.codprodutobarra = 957020




update tblnotafiscalprodutobarra 
set codprodutobarra = 986198
where tblnotafiscalprodutobarra.codnotafiscal in (
	select n.codnotafiscal
	from tblnotafiscal n 
	where n.codnaturezaoperacao  = 00000004
	and n.codpessoa = 30011016
	and n.saida >= '2019-08-01'
)
and tblnotafiscalprodutobarra.codprodutobarra = 957020



select * from tblnegocioformapagamento t where codnegocio = 2361636


delete from tblnegocioformapagamento  where codnegocioformapagamento  = 2385664

select * from tblmovimentotitulo t where codtitulo = 384193


delete from tblmovimentotitulo where codmovimentotitulo  = 857929

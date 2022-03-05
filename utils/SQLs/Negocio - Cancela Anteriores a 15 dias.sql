
delete from tblnegocioprodutobarra where tblnegocioprodutobarra.codnegocio in 
(
	select tblnegocio.codnegocio 
	from tblnegocio 
	where tblnegocio.codnegociostatus = 1
	and tblnegocio.lancamento < (current_date - interval '30 day')
	--and tblnegocio.codusuario in (select tblusuario.codusuario from tblusuario where usuario ilike 'esc%')
)

select tblnegocio.codnegocio, coalesce(observacoes || ' - ', '') || 'CANCELAMENTO AUTOMATICO - NEGOCIO NAO FECHADO COM MAIS DE 15 DIAS' 
from tblnegocio 
where tblnegocio.codnegociostatus = 1
and tblnegocio.lancamento < (current_date - interval '30 day')


update tblnegocio 
set codnegociostatus = 3
, observacoes = coalesce(observacoes || ' - ', '') || 'CANCELAMENTO AUTOMATICO - NEGOCIO NAO FECHADO COM MAIS DE 7 DIAS'
, alteracao = now()
, codusuarioalteracao = 1
where tblnegocio.codnegociostatus = 1
and tblnegocio.lancamento < (current_date - interval '7 day')
--and tblnegocio.codusuario in (select tblusuario.codusuario from tblusuario where usuario ilike 'esc%')

select * from tblusuario where codusuario = 1

select * from tblnegocioprodutobarra where codnegocioprodutobarra = 20131435

update tblnegocio set codnegociostatus = 2 where codnegocio = 20049883


select * from tblfilial order by codfilial 

select * from tblestoquelocal t order by codfilial 

select * from tbldistribuicaodfe t where codfilial = 905

select count(*) 
from tblnotafiscal t 
where emitida 
and nfeautorizacao is null 
and nfechave is not null 
and nfeinutilizacao is null 
and nfecancelamento is null


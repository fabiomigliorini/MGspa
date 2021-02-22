select setval('tblibptcache_tblibptcache_seq', 1, false)

delete from tblibptcache

INSERT INTO mgsis.tblibptcache
(codfilial, codestado, ncm, extarif, descricao, nacional, estadual, importado, municipal, tipo, vigenciainicio, vigenciafim, chave, versao, fonte, criacao, codusuariocriacao, alteracao, codusuarioalteracao)
select
	104 as codfilial, 
	8956 as codestado, 
	i.codigo, 
	coalesce(cast(i.ex as smallint), 0) as extarif, 
	LEFT(i.descricao, 400) as descricao, 
	cast(i.nacionalfederal as numeric) as nacional, 
	cast(i.estadual as numeric), 
	cast(i.importadosfederal as numeric) as importado, 
	cast(i.municipal as numeric), 
	cast(i.tipo as smallint) as tipo, 
	cast(i.vigenciainicio as date), 
	cast(i.vigenciafim as date), 
	i.chave, 
	i.versao, 
	i.fonte,
	date_trunc('second', now()) criacao, 
	1 as codusuariocriacao,
	date_trunc('second', now()) alteracao, 
	1 as codusuarioalteracao 
from tblibptaxmt211a i
left join tblncm n on (i.codigo = n.ncm)
where i.tipo = '0' 
--and n.codncm is null

select count(*) from tblibptcache t 

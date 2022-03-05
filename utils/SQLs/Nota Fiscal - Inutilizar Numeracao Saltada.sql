
-- Listagem 1a e ultima nota emitida nos ultimos 2 meses
select 
	nf.codfilial,
	nf.serie,
	nf.modelo,
	min(nf.numero) primeira, 
	max(nf.numero) ultima
from tblnotafiscal nf
where nf.emitida = true
and nf.numero != 0
and nf.emissao between date_trunc('month', now() - '2 months'::interval) and date_trunc('month', now())
group by
	nf.codfilial,
	nf.serie,
	nf.modelo
order by 1, 2, 3, 4, 5

-- Cria as notas nos GAPS das notas fiscais
insert into tblnotafiscal 
	(numero, codfilial, codestoquelocal, modelo, emitida, emissao, saida, codpessoa, codnaturezaoperacao, serie) 
select 
	num as numero, 
	:codfilial as codfilial, 
	:codestoquelocal as codestoquelocal, 
	:modelo as modelo, 
	true as emitida, 
	date_trunc('day', now()) as emissao, 
	date_trunc('day', now()) as saida, 
	1 as codpessoa, 
	1 as codnaturezaoperacao, 
	:serie as serie
	--num, nf.numero, nf.codnotafiscal
--from generate_series(2, 5) num
from generate_series(:numero_inicial, :numero_final) num
left join tblnotafiscal nf on (nf.numero = num and nf.codfilial = :codfilial and nf.serie = :serie and nf.modelo = :modelo and nf.emitida = true)
where nf.codnotafiscal is null
order by 1

/*
delete from tblnotafiscal where codnotafiscal in (
	select nf.codnotafiscal 
	from tblnotafiscal nf 
	where nf.codpessoa = 1 
	and nf.modelo = :modelo 
	and nf.codfilial = :codfilial 
	and nf.numero between :numero_inicial and :numero_final 
	and nf.nfeautorizacao is null 
	and nf.nfecancelamento is null 
	and nf.emitida = true 
	and nf.nfeinutilizacao is null
	order by codnotafiscal 
	desc
	limit 2000
)
delete from tblnotafiscal where codpessoa = 1 and modelo = :modelo and codfilial = :codfilial and numero between :numero_inicial and :numero_final and nfeautorizacao is null and nfecancelamento is null and emitida = true and nfeinutilizacao is null limit 1000
*/

--  Quantidade para inutilizar
select count(*)
from tblnotafiscal nf
where nf.emitida = true
and nf.codfilial = :codfilial
and nf.modelo = :modelo
and nf.serie = :serie
--and nf.saida = date_trunc('day', now())
and nf.nfeautorizacao is null
and nf.nfecancelamento is null
and nf.nfeinutilizacao is null
--and nf.numero between :numero_inicial and :numero_final
limit 1100

--  Comando para inutilizar as notas
select 'wget https://api.mgspa.mgpapelaria.com.br/api/v1/nfe-php/' || nf.codnotafiscal || '/inutilizar?justificativa=falha+de+sistema%2C+saltou+numeracao' as comando, numero
from tblnotafiscal nf
where nf.emitida = true
and nf.codfilial = :codfilial
and nf.modelo = :modelo
and nf.serie = :serie
--and nf.saida = date_trunc('day', now())
and nf.nfeautorizacao is null
and nf.nfecancelamento is null
and nf.nfeinutilizacao is null
and nf.numero between :numero_inicial and :numero_final
--and nf.numero > 10002
order by numero
limit 200

-- marca nota ja autorizada como nao autorizada
update tblnotafiscal 
set nfeautorizacao = null, nfedataautorizacao = null 
where emitida = true
and codfilial = :codfilial
and modelo = :modelo
and serie = :serie
and nfeautorizacao is not null
--and numero between 197949 and 197949
and numero = :numero 

/*
-- verifica quais nao foram inutilizadas na faixa
select *
from tblnotafiscal nf
where nf.emitida = true
and nf.codfilial = :codfilial
and nf.modelo = :modelo
and nf.serie = :serie
and nf.nfeinutilizacao is not null
and nf.numero between :numero_inicial and :numero_final
order by numero asc

-- verifica quantas notas inutilizadas na faixa
select count(*)
from tblnotafiscal nf
where nf.emitida = true
and nf.codfilial = :codfilial
and nf.modelo = :modelo
and nf.serie = :serie
and nf.nfeinutilizacao is not null
and nf.numero between :numero_inicial and :numero_final

*/




update tblnotafiscal set nfeautorizacao = null, nfedataautorizacao = null where codnotafiscal = :codnotafiscal

update tblnegocio set codfilial = :codfilial, codestoquelocal = :codestoquelocal where codnegocio = :codnegocio 


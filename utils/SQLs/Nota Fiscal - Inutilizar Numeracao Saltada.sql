
-- Cria as notas nos GAPS das notas fiscais
insert into tblnotafiscal 
	(numero, codfilial, codestoquelocal, modelo, emitida, emissao, saida, codpessoa, codnaturezaoperacao, serie) 
select 
	num as numero, 
	104 as codfilial, 
	104001 as codestoquelocal, 
	65 as modelo, 
	true as emitida, 
	date_trunc('day', now()) as emissao, 
	date_trunc('day', now()) as saida, 
	1 as codpessoa, 
	1 as codnaturezaoperacao, 
	1 as serie
	--num, nf.numero, nf.codnotafiscal
--from generate_series(2, 5) num
from generate_series(191960, 191991) num
left join tblnotafiscal nf on (nf.numero = num and nf.codfilial = 104 and nf.serie = 1 and nf.modelo = 65 and nf.emitida = true)
where nf.codnotafiscal is null
order by 1

--  Quantidade para inutilizar
select count(*)
from tblnotafiscal nf
where nf.emitida = true
and nf.codfilial = 104
and nf.modelo = 65
and nf.serie = 1
--and nf.saida = date_trunc('day', now())
and nf.nfeautorizacao is null
and nf.nfecancelamento is null
and nf.nfeinutilizacao is null
--and nf.numero between 391796 and 511054
--and nf.numero > 10979
limit 1100

--  Comando para inutilizar as notas
select 'wget https://api.mgspa.mgpapelaria.com.br/api/v1/nfe-php/' || nf.codnotafiscal || '/inutilizar?justificativa=falha+de+sistema%2C+saltou+numeracao' as comando, numero
from tblnotafiscal nf
where nf.emitida = true
and nf.codfilial = 104
and nf.modelo = 65
and nf.serie = 1
--and nf.saida = date_trunc('day', now())
and nf.nfeautorizacao is null
and nf.nfecancelamento is null
and nf.nfeinutilizacao is null
and nf.numero between 192213 and 192221
--and nf.numero > 10002
order by numero
limit 200


-- marca nota ja autorizada como nao autorizada
update tblnotafiscal 
set nfeautorizacao = null, nfedataautorizacao = null 
where emitida = true
and codfilial = 104
and modelo = 65
and serie = 1
and nfeautorizacao is not null
and numero between 00192229 and 00192229
--and numero in (192211)

-- verifica quais nao foram inutilizadas na faixa
select *
from tblnotafiscal nf
where nf.emitida = true
and nf.codfilial = 104
and nf.modelo = 65
and nf.serie = 1
and nf.nfeinutilizacao is not null
and nf.numero between 192213 and 192221
order by numero asc

-- verifica quantas notas inutilizadas na faixa
select count(*)
from tblnotafiscal nf
where nf.emitida = true
and nf.codfilial = 104
and nf.modelo = 65
and nf.serie = 1
and nf.nfeinutilizacao is not null
and nf.numero between 537117 and 541781









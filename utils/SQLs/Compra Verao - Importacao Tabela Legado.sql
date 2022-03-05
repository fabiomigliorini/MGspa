drop table tblcompraverao;

create table tblcompraverao as
select 
	npb.codnegocioprodutobarra,
	npb.codprodutobarra,
	nat.naturezaoperacao, 
	nat.codoperacao,
	p.codproduto,
	p.produto,
	coalesce(pb.referencia, pv.referencia, p.referencia) as referencia,
	pb.barras,
	m.marca,
	coalesce(pe.preco, p.preco * coalesce(pe.quantidade, 1)) as preco,
	npb.quantidade * (case when nat.codoperacao = 1 then 1 else -1 end) as quantidade
from tblnegocio n
inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = n.codnaturezaoperacao)
inner join tblnegocioprodutobarra npb on (npb.codnegocio = n.codnegocio)
inner join tblprodutobarra pb on (pb.codprodutobarra = npb.codprodutobarra)
left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
inner join tblprodutovariacao pv on (pv.codprodutovariacao = pb.codprodutovariacao)
inner join tblproduto p on (p.codproduto = pb.codproduto)
inner join tblmarca m on (m.codmarca = p.codmarca)
where n.codpessoa = 12376
and n.codnegociostatus = 2;

alter table tblcompraverao add total numeric(14,2);

update tblcompraverao set total = quantidade * preco;

alter table tblcompraverao add precoverao numeric(14,2);

update tblcompraverao set precoverao = (select valorvenda_conv from tblestoqueverao where codbar = barras limit 1 );

update tblcompraverao set precoverao = preco where precoverao is null;

alter table tblcompraverao add totalverao numeric(14,2);

update tblcompraverao set totalverao = quantidade * precoverao;

-- Geração Planilha
select * from tblcompraverao order by marca, produto, barras, quantidade, codnegocioprodutobarra


select * from tblcompraverao where precoverao is null


select * from tblestoqueverao 

select naturezaoperacao, sum(total), sum(totalverao) from tblcompraverao group by naturezaoperacao order by 2 desc;

select marca, sum(total) from tblcompraverao group by marca order by 2 desc;



/*
select count(*) 
from tblestoqueverao

select * from tblestoqueverao order by codigo desc

alter table tblestoqueverao add codproduto bigint

update tblestoqueverao set codprodutobarra = (select pb.codprodutobarra from tblprodutobarra pb where pb.barras = tblestoqueverao.codbar)

update tblestoqueverao set codprodutovariacao = (select pb.codprodutovariacao from tblprodutobarra pb where pb.codprodutobarra = tblestoqueverao.codprodutobarra)

update tblestoqueverao set codproduto = (select pb.codproduto from tblprodutobarra pb where pb.codprodutobarra = tblestoqueverao.codprodutobarra)

select dataultmvenda, * from tblestoqueverao where codproduto is null

alter table tblestoqueverao add valorvenda_conv numeric(14,2)

alter table tblestoqueverao add codncm bigint

alter table tblestoqueverao add codprodutovariacao bigint


update tblestoqueverao set importar = true where dataultmvenda_conv >= '2021-01-01'

update tblestoqueverao set codigo_conv = replace(codigo, ',', '.')::numeric(14,2)

update tblestoqueverao set quantidade_conv = replace(quantidade, ',', '.')::numeric(14,2)

update tblestoqueverao set valorvenda_conv = replace(valorvenda, ',', '.')::numeric(14,2)

update tblestoqueverao set dataultmvenda_conv = TO_TIMESTAMP(dataultmvenda, 'DD/MM/YYYY HH24:MI:SS')

update tblestoqueverao set ultaltera_conv = TO_TIMESTAMP(ultaltera, 'DD/MM/YYYY HH24:MI:SS')

select ultaltera , ultaltera_conv, codigo, codigo_conv from tblestoqueverao order by ultaltera_conv desc

update tblestoqueverao set codncm = coalesce((select n.codncm from tblncm n where n.ncm = codigofiscal), 14089)

select codigofiscal, * from tblestoqueverao where codncm is null  and importar


select count(*) 
from tblestoqueverao 
where importar
and codproduto is null

update tblestoqueverao
set codproduto = nextval('tblproduto_codproduto_seq')
where importar
and codproduto is null

update tblestoqueverao 
set codproduto = p.codproduto
from tblproduto p 
where p.referencia = 'Verao ' || tblestoqueverao.codigo_conv

update tblestoqueverao 
set codprodutovariacao = pv.codprodutovariacao
from tblprodutovariacao pv 
where tblestoqueverao.codprodutovariacao is null
and pv.codproduto = tblestoqueverao.codproduto 

INSERT INTO mgsis.tblproduto
(codproduto, produto, referencia, codunidademedida, codsubgrupoproduto, codmarca, preco, importado, codtributacao, inativo, codtipoproduto, site, descricaosite, alteracao, codusuarioalteracao, criacao, codusuariocriacao, codncm, codcest, observacoes, codopencart, codopencartvariacao, peso, altura, largura, profundidade, vendesite, metakeywordsite, metadescriptionsite, codprodutoimagem, abcignorar, abcposicao, abccategoria, abc, codcestanterior)
select
	codproduto,
	'Verao ' || coalesce(fornecedor, '') || ' ' || coalesce(discriminacao, '') as  produto,
	'Verao ' || codigo_conv as referencia, 
	2 as codunidademedida, 
	90003999 as codsubgrupoproduto, 
	695 as codmarca, 
	valorvenda_conv as preco, 
	false as importado, 
	1 as codtributacao, 
	null as inativo, 
	0 as codtipoproduto, 
	false as site, 
	null as descricaosite, 
	'2022-02-12 16:40' as alteracao, 
	1 as codusuarioalteracao, 
	'2022-02-12 16:40' as criacao, 
	1 codusuariocriacao, 
	codncm, 
	null as codcest, 
	'Importado Cadastro Verao Papelaria - Codigo ' || codigo_conv as observacoes, 
	null as codopencart, 
	null as codopencartvariacao, 
	null as peso, 
	null as altura, 
	null as largura, 
	null as profundidade, 
	false as vendesite, 
	null as metakeywordsite, 
	null as metadescriptionsite, 
	null as codprodutoimagem, 
	false as abcignorar, 
	null as abcposicao, 
	4 as abccategoria, 
	'C' as abc, 
	null as codcestanterior
from tblestoqueverao
where importar
and codprodutobarra is null
order by codproduto asc

update tblestoqueverao
set codprodutovariacao = nextval('tblprodutovariacao_codprodutovariacao_seq')
where importar
and codprodutovariacao is null

update tblestoqueverao
set codprodutobarra = nextval('tblprodutobarra_codprodutobarra_seq')
where importar
and codprodutobarra is null

INSERT INTO mgsis.tblprodutovariacao
(codprodutovariacao, codproduto, alteracao, codusuarioalteracao, criacao, codusuariocriacao)
select
	codprodutovariacao,
	codproduto,
	'2022-02-12 16:40' as alteracao, 
	1 as codusuarioalteracao, 
	'2022-02-12 16:40' as criacao, 
	1 codusuariocriacao 
from tblestoqueverao
where codproduto between 54855 and 62021
order by codproduto


INSERT INTO mgsis.tblprodutobarra
(codprodutobarra, codproduto, barras, alteracao, codusuarioalteracao, criacao, codusuariocriacao, codprodutovariacao)
select
	distinct
	ev.codprodutobarra,
	ev.codproduto, 
	CASE
		WHEN LENGTH(codbar) > 10 THEN trim(codbar)
		ELSE 'V' || codigo_conv
	end as barras, 
	'2022-02-12 16:40'::timestamp as alteracao, 
	1 as codusuarioalteracao, 
	'2022-02-12 16:40'::timestamp as criacao, 
	1 codusuariocriacao, 
	ev.codprodutovariacao
from tblestoqueverao ev
left join tblprodutobarra pb on (pb.barras = ev.codbar)
where ev.codproduto between 54855 and 62021
and ev.importar
and pb.codprodutobarra is null

select dataultmvenda, codigo_conv, *  from tblestoqueverao t where codbar ilike '%7898507796525%' order by dataultmvenda  desc 

update tblestoqueverao set importar = false where codigo_conv = 1125 


select trim(codbar)
from tblestoqueverao
group by trim(codbar)
having count(*) > 1


select * from tblestoqueverao where codigo_conv = 2245

select * from tblpessoa where cnpj = 04576775000594

select * from tblfilial order by codfilial asc

select * from tblestoquelocal t 

-- mgsis.tblnotafiscal_numero_101_1_55_seq definition

-- DROP SEQUENCE mgsis.tblnotafiscal_numero_101_1_55_seq;

CREATE SEQUENCE mgsis.tblnotafiscal_numero_105_1_65_seq;
	

select produto from tblproduto where produto ilike '%�%'

select produto, replace(produto, :errado, :correto), * from tblproduto where produto ilike '%' || :errado || '%'

update tblproduto set produto = replace(produto, :errado, :correto) where produto ilike '%' || :errado || '%' 

select codfilial, codestoquelocal, * from tblnegocio where codpessoa = 12376 order by lancamento desc



update tblnotafiscal set emissao = '2022-02-14 16:23', saida = '2022-02-14 16:23', numero = 0, nfechave = null where codnotafiscal = 2083490



update tblnegocio set codnegociostatus = 2 where codnegocio = 2539890



select * from tblnegocioformapagamento t where codnegocio = 2539890

select * from tblnegocio t where codnegocio = 2539890



update tblnegocio set codpessoa = 12410 where codnegocio = 2540813



update tblnegocio set codpessoa = 12410, codnaturezaoperacao = 00000015 where codnegocio = 2542129

*/




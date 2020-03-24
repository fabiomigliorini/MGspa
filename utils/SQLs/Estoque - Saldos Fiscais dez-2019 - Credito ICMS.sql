-- limpa tabela
drop table tblestoque2019creditoicms;

-- cria tabela com base na view
refresh materialized view mvwestoque2019;
create table tblestoque2019creditoicms as select * from mvwestoque2019 e;

-- codtributacao
alter table tblestoque2019creditoicms add codtributacao bigint;
update tblestoque2019creditoicms set codtributacao = p.codtributacao from tblproduto p where p.codproduto = tblestoque2019creditoicms.codproduto;

-- registros por tributacao
/*
select 
	codtributacao,
	count(*), 
	sum(preco * quant) as venda
from tblestoque2019creditoicms 
group by 
	codtributacao
order by 1
*/

-- codigo do registro da ultima entrada
alter table tblestoque2019creditoicms add codnotafiscalprodutobarra bigint;

-- Busca Compras
update tblestoque2019creditoicms set codnotafiscalprodutobarra = (
	select codnotafiscalprodutobarra
	from tblprodutobarra pb
	inner join tblnotafiscalprodutobarra nfpb on (nfpb.codprodutobarra = pb.codprodutobarra)
	inner join tblnotafiscal nf on (nf.codnotafiscal = nfpb.codnotafiscal and nf.codfilial in (101, 102, 103, 104) and nf.saida <= '2019-12-31 23:59:59.9')
	inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = nf.codnaturezaoperacao and nat.compra = true)
	where pb.codproduto = tblestoque2019creditoicms.codproduto
	and nfpb.quantidade > 0
	--where pb.codproduto = 106484
	order by emissao desc limit 1
) where codnotafiscalprodutobarra  is null;

-- Busca Bonificacoes
update tblestoque2019creditoicms set codnotafiscalprodutobarra = (
	select codnotafiscalprodutobarra
	from tblprodutobarra pb
	inner join tblnotafiscalprodutobarra nfpb on (nfpb.codprodutobarra = pb.codprodutobarra)
	inner join tblnotafiscal nf on (nf.codnotafiscal = nfpb.codnotafiscal and nf.codfilial in (101, 102, 103, 104) and nf.saida <= '2019-12-31 23:59:59.9')
	inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = nf.codnaturezaoperacao and nat.codnaturezaoperacao = 8)
	where pb.codproduto = tblestoque2019creditoicms.codproduto
	and nfpb.quantidade > 0
	--where pb.codproduto = 106484
	order by emissao desc limit 1
) where codnotafiscalprodutobarra  is null;

-- registros que ficaram sem entrada
/*
select 
	codnotafiscalprodutobarra is null,
	--codtributacao,
	count(*),
	sum(preco * quant) as venda	
from tblestoque2019creditoicms 
group by 
	codnotafiscalprodutobarra is null
	--codtributacao
order by 1, 2
*/

-- codnotafiscal / chave nfe
alter table tblestoque2019creditoicms add codnotafiscal bigint;
alter table tblestoque2019creditoicms add nfechave varchar(100);
alter table tblestoque2019creditoicms add emissao date;

-- custo da ultima compra
alter table tblestoque2019creditoicms add custoultimaentrada numeric (20,6);

-- percentual ICMS garantido
alter table tblestoque2019creditoicms add icmsgarantidopercentual numeric (5,2);
alter table tblestoque2019creditoicms add icmsgarantidovalor numeric (14,2);
alter table tblestoque2019creditoicms add icmsgarantidovalortotal numeric (14,2);

-- percentual ICMS garantido
alter table tblestoque2019creditoicms add icmsorigempercentual numeric (5,2);
alter table tblestoque2019creditoicms add icmsorigemvalor numeric (14,2);
alter table tblestoque2019creditoicms add icmsorigemvalortotal numeric (14,2);

-- busca dados das notas
with notas as (
	select 
		nfpb.codnotafiscalprodutobarra,
		nfpb.codnotafiscal, 
		nf.nfechave, 
		nf.emissao,
		case when c.codestado != 8956 then 17.0 else 0.0 end as icmsgarantidopercentual,
		(nfpb.valortotal - coalesce(nfpb.valordesconto, 0)) / case when (coalesce(nfpb.quantidade, 1) = 0) then 1 else coalesce(nfpb.quantidade, 1) end / coalesce(pe.quantidade, 1) as custoultimaentrada, 
		icmspercentual as icmsorigempercentual
	from tblnotafiscalprodutobarra nfpb
	inner join tblprodutobarra pb on (pb.codprodutobarra = nfpb.codprodutobarra)
	left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
	inner join tblnotafiscal nf on (nf.codnotafiscal = nfpb.codnotafiscal)
	inner join tblpessoa p on (p.codpessoa = nf.codpessoa)
	inner join tblcidade c on (c.codcidade = p.codcidade)
	--where nfpb.codnotafiscalprodutobarra = 4042836
)
update tblestoque2019creditoicms
set codnotafiscal = notas.codnotafiscal
, nfechave = notas.nfechave
, emissao = notas.emissao
, custoultimaentrada = notas.custoultimaentrada
, icmsgarantidopercentual = notas.icmsgarantidopercentual
, icmsorigempercentual = notas.icmsorigempercentual
from notas
where notas.codnotafiscalprodutobarra = tblestoque2019creditoicms.codnotafiscalprodutobarra;

update tblestoque2019creditoicms set icmsorigempercentual = 4 where icmsorigempercentual between 3.9 and 4.1;
update tblestoque2019creditoicms set icmsorigempercentual = 7 where icmsorigempercentual between 6.5 and 7.1;
update tblestoque2019creditoicms set icmsorigempercentual = 7 where icmsorigempercentual > 7;
update tblestoque2019creditoicms set icmsorigempercentual = null where icmsorigempercentual =0;

update tblestoque2019creditoicms 
set icmsorigempercentual = nf.percentual 
from tblnotafiscalcreditoicmssimples nf
where tblestoque2019creditoicms.icmsorigempercentual is null
and tblestoque2019creditoicms.codnotafiscal = nf.codnotafiscal
and nf.percentual > 0;


-- valores do ICMS
update tblestoque2019creditoicms set icmsgarantidopercentual = null where codtributacao != 1;
update tblestoque2019creditoicms set icmsorigempercentual = null where codtributacao != 1;
update tblestoque2019creditoicms set icmsgarantidovalor = custoultimaentrada * (icmsgarantidopercentual / 100);
update tblestoque2019creditoicms set icmsgarantidovalortotal = icmsgarantidovalor * quant;
update tblestoque2019creditoicms set icmsorigemvalor = custoultimaentrada * (icmsorigempercentual / 100), icmsorigemvalortotal = custoultimaentrada * (icmsorigempercentual / 100) * quant;
update tblestoque2019creditoicms set icmsorigemvalortotal = icmsorigemvalor * quant;

select 
	codtributacao,
	codnotafiscalprodutobarra is null,
	count(*) as itens,
	sum(preco * quant) as venda,
	sum(custoultimaentrada * quant) as custo,
	sum(icmsgarantidovalortotal) as icmsgarantidovalortotal,
	sum(icmsorigemvalortotal) as icmsorigemvalortotal
from tblestoque2019creditoicms 
--where inativo is not null
group by 
	codtributacao,
	codnotafiscalprodutobarra is null
order by 1, 2

--select * from tblestoque2019creditoicms where codtributacao = 1 order by produto limit 500

/*

select * 
from tblestoque2019creditoicms 
--where codtributacao = 1 
order by produto, codproduto, codfilial
--limit 500
*/

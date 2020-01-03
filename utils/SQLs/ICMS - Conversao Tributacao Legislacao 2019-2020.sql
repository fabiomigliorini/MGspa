-- Backup da tributacao
create table tbltributacaonaturezaoperacao_backup as select * from tbltributacaonaturezaoperacao 

-- Passa pra Tributado 00 tudo que for outras 90
update tbltributacaonaturezaoperacao set icmscst = 00, icmslpbase = 100, icmslppercentual = 17 where icmscst=90

-- Passa pra Aliquota 12% tudo que for fora do estado
update tbltributacaonaturezaoperacao set icmslppercentual = 12 where icmslppercentual = 17 and codestado is null

-- Adiciona coluna com BIT - Bens de Informatica e Telecomunicacao
alter table tbltributacaonaturezaoperacao add bit boolean not null default false

--adiciona campo com percentual da base de calculo do icms, pra quando houver reducao
alter table tblnotafiscalprodutobarra add icmsbasepercentual numeric (5,2)

-- Adiciona Parametrizacao Venda / BIT / Tributada
insert into tbltributacaonaturezaoperacao (
       codtributacao, codnaturezaoperacao, 
       codcfop, icmsbase, icmspercentual, codestado, csosn, codtipoproduto, 
       acumuladordominiovista, acumuladordominioprazo, historicodominio, 
       movimentacaofisica, movimentacaocontabil, alteracao, codusuarioalteracao, 
       criacao, codusuariocriacao, ncm, icmscst, icmslpbase, icmslppercentual, 
       ipicst, piscst, pispercentual, cofinscst, cofinspercentual, csllpercentual, 
       irpjpercentual, certidaosefazmt, fethabkg, iagrokg, funruralpercentual, 
       senarpercentual, observacoesnf, "bit"
)
SELECT codtributacao, codnaturezaoperacao, 
       codcfop, icmsbase, icmspercentual, codestado, csosn, codtipoproduto, 
       acumuladordominiovista, acumuladordominioprazo, historicodominio, 
       movimentacaofisica, movimentacaocontabil, alteracao, codusuarioalteracao, 
       criacao, codusuariocriacao, ncm, 20 as icmscst, 52.94 as icmslpbase, icmslppercentual, 
       ipicst, piscst, pispercentual, cofinscst, cofinspercentual, csllpercentual, 
       irpjpercentual, certidaosefazmt, fethabkg, iagrokg, funruralpercentual, 
       senarpercentual, observacoesnf, true as "bit"
  from tbltributacaonaturezaoperacao 
  where codnaturezaoperacao = 1 and codtributacao = 1 and codtipoproduto = 0

-- Adiciona Parametrizacao Venda / BIT / Substituicao
insert into tbltributacaonaturezaoperacao (
       codtributacao, codnaturezaoperacao, 
       codcfop, icmsbase, icmspercentual, codestado, csosn, codtipoproduto, 
       acumuladordominiovista, acumuladordominioprazo, historicodominio, 
       movimentacaofisica, movimentacaocontabil, alteracao, codusuarioalteracao, 
       criacao, codusuariocriacao, ncm, icmscst, icmslpbase, icmslppercentual, 
       ipicst, piscst, pispercentual, cofinscst, cofinspercentual, csllpercentual, 
       irpjpercentual, certidaosefazmt, fethabkg, iagrokg, funruralpercentual, 
       senarpercentual, observacoesnf, "bit"
)
SELECT codtributacao, codnaturezaoperacao, 
       codcfop, icmsbase, icmspercentual, codestado, csosn, codtipoproduto, 
       acumuladordominiovista, acumuladordominioprazo, historicodominio, 
       movimentacaofisica, movimentacaocontabil, alteracao, codusuarioalteracao, 
       criacao, codusuariocriacao, ncm, icmscst, icmslpbase, icmslppercentual, 
       ipicst, piscst, pispercentual, cofinscst, cofinspercentual, csllpercentual, 
       irpjpercentual, certidaosefazmt, fethabkg, iagrokg, funruralpercentual, 
       senarpercentual, observacoesnf, true as "bit"
  from tbltributacaonaturezaoperacao 
  where codnaturezaoperacao = 1 and codtributacao = 3 and codtipoproduto = 0 and ncm is null

-- Adiciona Parametrizacao Devolucao Venda / BIT / Tributada
insert into tbltributacaonaturezaoperacao (
       codtributacao, codnaturezaoperacao, 
       codcfop, icmsbase, icmspercentual, codestado, csosn, codtipoproduto, 
       acumuladordominiovista, acumuladordominioprazo, historicodominio, 
       movimentacaofisica, movimentacaocontabil, alteracao, codusuarioalteracao, 
       criacao, codusuariocriacao, ncm, icmscst, icmslpbase, icmslppercentual, 
       ipicst, piscst, pispercentual, cofinscst, cofinspercentual, csllpercentual, 
       irpjpercentual, certidaosefazmt, fethabkg, iagrokg, funruralpercentual, 
       senarpercentual, observacoesnf, "bit"
)
SELECT codtributacao, codnaturezaoperacao, 
       codcfop, icmsbase, icmspercentual, codestado, csosn, codtipoproduto, 
       acumuladordominiovista, acumuladordominioprazo, historicodominio, 
       movimentacaofisica, movimentacaocontabil, alteracao, codusuarioalteracao, 
       criacao, codusuariocriacao, ncm, 20 as icmscst, 52.94 as icmslpbase, icmslppercentual, 
       ipicst, piscst, pispercentual, cofinscst, cofinspercentual, csllpercentual, 
       irpjpercentual, certidaosefazmt, fethabkg, iagrokg, funruralpercentual, 
       senarpercentual, observacoesnf, true as "bit"
  from tbltributacaonaturezaoperacao 
  where codnaturezaoperacao = 2 and codtributacao = 1 and codtipoproduto = 0

-- Adiciona Parametrizacao Devolucao Venda / BIT / Substituicao
insert into tbltributacaonaturezaoperacao (
       codtributacao, codnaturezaoperacao, 
       codcfop, icmsbase, icmspercentual, codestado, csosn, codtipoproduto, 
       acumuladordominiovista, acumuladordominioprazo, historicodominio, 
       movimentacaofisica, movimentacaocontabil, alteracao, codusuarioalteracao, 
       criacao, codusuariocriacao, ncm, icmscst, icmslpbase, icmslppercentual, 
       ipicst, piscst, pispercentual, cofinscst, cofinspercentual, csllpercentual, 
       irpjpercentual, certidaosefazmt, fethabkg, iagrokg, funruralpercentual, 
       senarpercentual, observacoesnf, "bit"
)
SELECT codtributacao, codnaturezaoperacao, 
       codcfop, icmsbase, icmspercentual, codestado, csosn, codtipoproduto, 
       acumuladordominiovista, acumuladordominioprazo, historicodominio, 
       movimentacaofisica, movimentacaocontabil, alteracao, codusuarioalteracao, 
       criacao, codusuariocriacao, ncm, icmscst, icmslpbase, icmslppercentual, 
       ipicst, piscst, pispercentual, cofinscst, cofinspercentual, csllpercentual, 
       irpjpercentual, certidaosefazmt, fethabkg, iagrokg, funruralpercentual, 
       senarpercentual, observacoesnf, true as "bit"
  from tbltributacaonaturezaoperacao 
  where codnaturezaoperacao = 2 and codtributacao = 3 and codtipoproduto = 0 and ncm is null



-- Adiciona Parametrizacao Compra / BIT / Tributada
insert into tbltributacaonaturezaoperacao (
       codtributacao, codnaturezaoperacao, 
       codcfop, icmsbase, icmspercentual, codestado, csosn, codtipoproduto, 
       acumuladordominiovista, acumuladordominioprazo, historicodominio, 
       movimentacaofisica, movimentacaocontabil, alteracao, codusuarioalteracao, 
       criacao, codusuariocriacao, ncm, icmscst, icmslpbase, icmslppercentual, 
       ipicst, piscst, pispercentual, cofinscst, cofinspercentual, csllpercentual, 
       irpjpercentual, certidaosefazmt, fethabkg, iagrokg, funruralpercentual, 
       senarpercentual, observacoesnf, "bit"
)
SELECT codtributacao, codnaturezaoperacao, 
       codcfop, icmsbase, icmspercentual, codestado, csosn, codtipoproduto, 
       acumuladordominiovista, acumuladordominioprazo, historicodominio, 
       movimentacaofisica, movimentacaocontabil, alteracao, codusuarioalteracao, 
       criacao, codusuariocriacao, ncm, 20 as icmscst, 52.94 as icmslpbase, icmslppercentual, 
       ipicst, piscst, pispercentual, cofinscst, cofinspercentual, csllpercentual, 
       irpjpercentual, certidaosefazmt, fethabkg, iagrokg, funruralpercentual, 
       senarpercentual, observacoesnf, true as "bit"
  from tbltributacaonaturezaoperacao 
  where codnaturezaoperacao = 4 and codtributacao = 1 and codtipoproduto = 0

-- Adiciona Parametrizacao Compra / BIT / Substituicao
insert into tbltributacaonaturezaoperacao (
       codtributacao, codnaturezaoperacao, 
       codcfop, icmsbase, icmspercentual, codestado, csosn, codtipoproduto, 
       acumuladordominiovista, acumuladordominioprazo, historicodominio, 
       movimentacaofisica, movimentacaocontabil, alteracao, codusuarioalteracao, 
       criacao, codusuariocriacao, ncm, icmscst, icmslpbase, icmslppercentual, 
       ipicst, piscst, pispercentual, cofinscst, cofinspercentual, csllpercentual, 
       irpjpercentual, certidaosefazmt, fethabkg, iagrokg, funruralpercentual, 
       senarpercentual, observacoesnf, "bit"
)
SELECT codtributacao, codnaturezaoperacao, 
       codcfop, icmsbase, icmspercentual, codestado, csosn, codtipoproduto, 
       acumuladordominiovista, acumuladordominioprazo, historicodominio, 
       movimentacaofisica, movimentacaocontabil, alteracao, codusuarioalteracao, 
       criacao, codusuariocriacao, ncm, icmscst, icmslpbase, icmslppercentual, 
       ipicst, piscst, pispercentual, cofinscst, cofinspercentual, csllpercentual, 
       irpjpercentual, certidaosefazmt, fethabkg, iagrokg, funruralpercentual, 
       senarpercentual, observacoesnf, true as "bit"
  from tbltributacaonaturezaoperacao 
  where codnaturezaoperacao = 4 and codtributacao = 3 and codtipoproduto = 0 and ncm is null







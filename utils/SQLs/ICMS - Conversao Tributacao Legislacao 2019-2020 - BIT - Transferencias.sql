
-- Adiciona Parametrizacao Transf Saida / BIT / Tributada
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
  where codnaturezaoperacao = 00000015 and codtributacao = 1 and codtipoproduto = 0

-- Adiciona Parametrizacao Transf Saida / BIT / Substituicao
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
  where codnaturezaoperacao = 00000015 and codtributacao = 3 and codtipoproduto = 0 and ncm is null



-- Adiciona Parametrizacao Transf Entrada / BIT / Tributada
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
  where codnaturezaoperacao = 00000016 and codtributacao = 1 and codtipoproduto = 0

-- Adiciona Parametrizacao Transf Entrada / BIT / Substituicao
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
  where codnaturezaoperacao = 00000016 and codtributacao = 3 and codtipoproduto = 0 and ncm is null

-- Corrige CST PIS/COFINS nas transferencias
update tbltributacaonaturezaoperacao set piscst = 8 where piscst = 4 and codnaturezaoperacao in (15, 16)
update tbltributacaonaturezaoperacao set cofinscst = 8 where cofinscst = 4 and codnaturezaoperacao in (15, 16)



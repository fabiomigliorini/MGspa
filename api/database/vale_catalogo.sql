-- =====================================================================
-- Catalogo de modelos de vale compras: evolui IN PLACE (milestone 1).
--
-- Os 204 modelos nunca se movem -- e tudo RENAME, nenhum dado viaja.
--   tblvalecompramodelo             -> tblvalemodelo
--   tblvalecompramodeloprodutobarra -> tblvalemodeloprodutobarra
--
-- Decisao 11 do plano: prefixo "tblnegocio" so em quem aponta para
-- codnegocio. O catalogo nao aponta, entao e "tblvalemodelo".
-- Decisao 10: nada com o nome "valecompra" sobrevive no catalogo --
-- por isso as sequences tambem sao renomeadas.
-- Decisao 12: modelo enxuto. Saem "desconto" (0 de 204 usam) e
-- "totalprodutos" (identico a "total" nos 204); "turma" e "ano"
-- colapsam dentro da descricao; "total" vira "valorprodutos", que
-- mapeia 1:1 para a face do vale emitido.
-- Decisao 13: favorecido vira opcional (vale ao portador).
--
-- A FK de tblvalecompra acompanha o rename sozinha. Quem quebra e o
-- PHP: o modulo vale compras do MGLara (previsto, volta no milestone 4)
-- e 4 pontos da API que sao repontados no mesmo commit.
--
-- REAPLICAVEL: o bloco inteiro so roda se a tabela antiga existir.
-- E isso que protege o UPDATE de concatenacao de rodar duas vezes.
-- =====================================================================

BEGIN;

DO $$
BEGIN

IF to_regclass('tblvalecompramodelo') IS NULL THEN
  RAISE NOTICE 'vale_catalogo.sql: ja aplicado, nada a fazer.';
  RETURN;
END IF;

-- ---------------------------------------------------------------
-- 1. Cabecalho do modelo
-- ---------------------------------------------------------------
ALTER TABLE tblvalecompramodelo RENAME TO tblvalemodelo;
ALTER TABLE tblvalemodelo RENAME COLUMN codvalecompramodelo TO codvalemodelo;
ALTER TABLE tblvalemodelo RENAME COLUMN total               TO valorprodutos;
ALTER SEQUENCE tblvalecompramodelo_codvalecompramodelo_seq
      RENAME TO tblvalemodelo_codvalemodelo_seq;

-- A coluna "modelo" passa a ser a descricao unica: sem campo novo,
-- sem rename. 63 dos 204 estouram varchar(50) depois de concatenar.
ALTER TABLE tblvalemodelo ALTER COLUMN modelo TYPE varchar(100);

-- Concatena turma e ano na descricao. A turma so entra quando
-- acrescenta informacao: em 105 dos 139 modelos com turma o texto ja
-- esta dentro de "modelo", e concatenar cru duplicaria literalmente
-- ("Mini Maternal, Maternal, Jardim 1 e 2 - Mini Maternal, Maternal,
-- Jardim 1 e 2 - 2020"). Nada se perde -- o texto continua visivel.
UPDATE tblvalemodelo
   SET modelo = concat_ws(' - ',
         modelo,
         CASE
           WHEN coalesce(btrim(turma), '') = ''                      THEN NULL
           WHEN position(lower(btrim(turma)) in lower(modelo)) > 0   THEN NULL
           ELSE btrim(turma)
         END,
         ano);

-- Vale ao portador: sem escola, o favorecido do vale emitido vira
-- Consumidor (codpessoa 1). No catalogo o campo fica opcional.
ALTER TABLE tblvalemodelo ALTER COLUMN codpessoafavorecido DROP NOT NULL;

ALTER TABLE tblvalemodelo
  DROP COLUMN turma,
  DROP COLUMN ano,
  DROP COLUMN desconto,
  DROP COLUMN totalprodutos;

-- ---------------------------------------------------------------
-- 2. Itens do modelo
-- ---------------------------------------------------------------
ALTER TABLE tblvalecompramodeloprodutobarra RENAME TO tblvalemodeloprodutobarra;
ALTER TABLE tblvalemodeloprodutobarra
  RENAME COLUMN codvalecompramodeloprodutobarra TO codvalemodeloprodutobarra;
ALTER TABLE tblvalemodeloprodutobarra
  RENAME COLUMN codvalecompramodelo TO codvalemodelo;
ALTER SEQUENCE tblvalecompramodeloprodutobarra_codvalecompraprodutobarra_seq
      RENAME TO tblvalemodeloprodutobarra_codvalemodeloprodutobarra_seq;

-- Nomes de valor iguais aos de tblnegocioprodutobarra e aos da
-- tblnegociovaleprodutobarra do milestone 2, que e semeada a partir
-- daqui: a copia vira campo a campo, sem tabela de-para.
ALTER TABLE tblvalemodeloprodutobarra RENAME COLUMN preco TO valorunitario;
ALTER TABLE tblvalemodeloprodutobarra RENAME COLUMN total TO valorprodutos;

RAISE NOTICE 'vale_catalogo.sql: catalogo migrado para tblvalemodelo.';

END $$;

COMMIT;

-- =====================================================================
-- Conferencia (esperado: 204 | 204 | 100 | 85)
--
-- select count(*), count(modelo), max(length(modelo)),
--        (select character_maximum_length from information_schema.columns
--          where table_name = 'tblvalemodelo' and column_name = 'modelo')
--   from tblvalemodelo;
-- =====================================================================

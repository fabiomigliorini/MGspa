-- =====================================================================
-- Juros por item: tblnegocioprodutobarra.valorjuros
--
-- POR QUE: ate' aqui o juros do parcelamento so' existia no cabecalho
-- (tblnegocio.valorjuros) e era rateado item a item NA HORA DE EMITIR a
-- nota, dentro do NotaFiscalNegocioService. Com a coluna, o rateio acontece
-- uma vez so', no negocio, e a nota passa a ser copia:
--
--   tblnotafiscalprodutobarra.valoroutras =
--       tblnegocioprodutobarra.valoroutras + tblnegocioprodutobarra.valorjuros
--
-- (a NF-e nao tem campo de juros no item -- ele tem que virar vOutro, e por
-- isso a soma acontece na passagem para a nota, nao antes.)
--
-- O QUE A COLUNA **NAO** FAZ: ela nao entra no valortotal do item. O
-- valortotal do item continua sendo produtos - desconto + frete + seguro +
-- outras, e a conferencia do PDV continua sendo
--
--   negocio.valortotal - negocio.valorjuros
--       = soma(itens.valortotal) + soma(vales.valortotal)
--
-- O que o confereTotais ganha e' uma checagem a mais: a soma do juros dos
-- itens e dos vales tem que dar o juros do cabecalho.
--
-- BACKFILL: 27 negocios em toda a base tem juros (756 itens). Como sao
-- poucos, o passado e' preenchido aqui e o codigo fica com UM caminho so' --
-- sem fallback para negocio antigo. A conta reproduz exatamente o rateio
-- que o gerador de nota fazia, para reemissao de nota antiga dar o mesmo
-- numero que foi autorizado na SEFAZ:
--
--   perc  = negocio.valorjuros / negocio.valorprodutos
--   juros = item.valortotal * perc      (e a sobra no ultimo item)
--
-- Idempotente: ADD COLUMN IF NOT EXISTS e o UPDATE so' toca item que ainda
-- esta' nulo. Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

-- ADD COLUMN sem DEFAULT e' so' metadado: nao reescreve os milhoes de
-- linhas da tabela. Ainda assim pega ACCESS EXCLUSIVE por um instante, e
-- esta e' a tabela mais quente do sistema junto com tblnegocio.
SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

ALTER TABLE tblnegocioprodutobarra ADD COLUMN IF NOT EXISTS valorjuros numeric(14,2);

-- ---------------------------------------------------------------------
-- Backfill dos negocios que tem juros
-- ---------------------------------------------------------------------
WITH base AS (
  SELECT n.codnegocio,
         n.valorjuros,
         n.valorprodutos
    FROM tblnegocio n
   WHERE coalesce(n.valorjuros, 0) <> 0
     AND coalesce(n.valorprodutos, 0) > 0
),
itens AS (
  SELECT npb.codnegocioprodutobarra,
         npb.codnegocio,
         round(npb.valortotal * b.valorjuros / b.valorprodutos, 2) AS juros,
         row_number() OVER (
           PARTITION BY npb.codnegocio
           ORDER BY npb.codnegocioprodutobarra DESC
         ) AS ultimo
    FROM tblnegocioprodutobarra npb
    JOIN base b ON b.codnegocio = npb.codnegocio
   WHERE npb.inativo IS NULL
     AND npb.valorjuros IS NULL
),
sobra AS (
  -- a diferenca do arredondamento vai para o ultimo item, do mesmo jeito
  -- que o gerador de nota fazia
  SELECT i.codnegocio,
         b.valorjuros - sum(i.juros) AS diferenca
    FROM itens i
    JOIN base b ON b.codnegocio = i.codnegocio
   GROUP BY i.codnegocio, b.valorjuros
)
UPDATE tblnegocioprodutobarra npb
   SET valorjuros = i.juros + CASE WHEN i.ultimo = 1 THEN coalesce(s.diferenca, 0) ELSE 0 END
  FROM itens i
  LEFT JOIN sobra s ON s.codnegocio = i.codnegocio
 WHERE npb.codnegocioprodutobarra = i.codnegocioprodutobarra;

-- Aborta se algum negocio ficou com a soma diferente do cabecalho: a
-- transacao inteira volta atras, e nada e' aplicado pela metade.
DO $$
DECLARE
  v_ruins bigint;
BEGIN
  SELECT count(*) INTO v_ruins
    FROM (
      SELECT n.codnegocio
        FROM tblnegocio n
        JOIN tblnegocioprodutobarra npb ON npb.codnegocio = n.codnegocio AND npb.inativo IS NULL
       WHERE coalesce(n.valorjuros, 0) <> 0
         AND coalesce(n.valorprodutos, 0) > 0
       GROUP BY n.codnegocio, n.valorjuros
      HAVING abs(coalesce(sum(npb.valorjuros), 0) - n.valorjuros) > 0.005
    ) q;
  IF v_ruins > 0 THEN
    RAISE EXCEPTION 'Backfill abortado: % negocio(s) com soma de juros nos itens diferente do cabecalho.', v_ruins;
  END IF;
END $$;

COMMIT;

-- ---------------------------------------------------------------------
-- Verificacao (fora da transacao)
-- ---------------------------------------------------------------------
\echo '== negocios com juros e a soma dos itens (diferenca tem que ser 0) =='
SELECT n.codnegocio,
       n.valorjuros AS cabecalho,
       sum(npb.valorjuros) AS soma_itens,
       n.valorjuros - sum(npb.valorjuros) AS diferenca
  FROM tblnegocio n
  JOIN tblnegocioprodutobarra npb ON npb.codnegocio = n.codnegocio AND npb.inativo IS NULL
 WHERE coalesce(n.valorjuros, 0) <> 0
 GROUP BY n.codnegocio, n.valorjuros
 ORDER BY n.codnegocio;

\echo '== itens com juros preenchido fora de negocio com juros (tem que ser 0) =='
SELECT count(*) AS itens_orfaos
  FROM tblnegocioprodutobarra npb
  JOIN tblnegocio n ON n.codnegocio = npb.codnegocio
 WHERE coalesce(npb.valorjuros, 0) <> 0
   AND coalesce(n.valorjuros, 0) = 0;

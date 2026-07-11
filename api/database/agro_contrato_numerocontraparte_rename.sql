-- =====================================================================
-- Renomeia tblcontrato.numerocomprador -> numerocontraparte.
-- "Contraparte" e mais generico (comprador na venda, vendedor na compra).
-- Reaplicavel (so renomeia se a coluna antiga ainda existir).
-- =====================================================================

BEGIN;

ALTER TABLE tblcontrato
  RENAME COLUMN numerocomprador TO numerocontraparte;

COMMIT;

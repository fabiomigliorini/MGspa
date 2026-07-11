-- =====================================================================
-- Remove tblcontrato.viacooperativa (redundante).
-- "Via cooperativa" = codpessoacooperativa IS NOT NULL.
-- Reaplicavel (DROP COLUMN IF EXISTS).
-- =====================================================================

BEGIN;

ALTER TABLE tblcontrato
  DROP COLUMN IF EXISTS viacooperativa;

COMMIT;

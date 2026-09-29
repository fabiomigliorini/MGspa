-- =====================================================================
-- Remove tblcontrato.viacooperativa (redundante).
-- "Via cooperativa" = codpessoacooperativa IS NOT NULL.
-- Reaplicavel (DROP COLUMN IF EXISTS).
-- =====================================================================
--
-- GUARDA: pula se a coluna ja nao existe.
-- Bloco unico (atomico); sem BEGIN/COMMIT pra nao deixar transacao aberta no DBeaver.
-- =====================================================================

SET lock_timeout = '5s';

DO $agro$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblcontrato' AND column_name = 'viacooperativa') THEN
    RAISE NOTICE '15 drop_viacooperativa: pulado (coluna ja removida)';
    RETURN;
  END IF;

  ALTER TABLE tblcontrato
    DROP COLUMN IF EXISTS viacooperativa;

  RAISE NOTICE '15 drop_viacooperativa: aplicado';
END
$agro$;

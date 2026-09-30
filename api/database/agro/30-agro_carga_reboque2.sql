-- =====================================================================
-- App Agro — Carga: 2º reboque (TASK-171)
-- =====================================================================
-- Caminhão "bitrem"/"rodotrem" tem 2 reboques. tblcarga.placacarreta virou o
-- 1º; este passo cria o 2º. Nenhum dos dois é obrigatório (validado no front
-- e no CargaSincronizarRequest, não no banco).
--
-- GUARDA: ADD COLUMN IF NOT EXISTS — rodar de novo nao faz nada.
-- =====================================================================

SET lock_timeout = '5s';

DO $agro$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblcarga' AND column_name = 'placacarreta2') THEN
    RAISE NOTICE '30 carga_reboque2: pulado (coluna ja existe)';
    RETURN;
  END IF;

  ALTER TABLE tblcarga ADD COLUMN placacarreta2 varchar(10);

  RAISE NOTICE '30 carga_reboque2: aplicado';
END
$agro$;

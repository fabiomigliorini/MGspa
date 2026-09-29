-- ============================================================
-- Agro: expectativa de colheita por plantio.
--
-- Cada talhão da safra (tblplantio) ganha a expectativa TOTAL de colheita em
-- sacas. Na tela o usuário pensa em sc/ha (âncora agronômica) e o total é
-- sc/ha × área plantada; aqui guardamos o total já calculado. Serve pra somar
-- a expectativa por fazenda e por safra e pra barra de progresso da colheita
-- (colhido ÷ expectativa).
--
-- Rodar no dev e na PROD. PostgreSQL. Idempotente.
-- ============================================================
--
-- GUARDA: pula se a coluna ja existe.
-- Bloco unico (atomico); sem BEGIN/COMMIT pra nao deixar transacao aberta no DBeaver.
-- =====================================================================

SET lock_timeout = '5s';

DO $agro$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblplantio' AND column_name = 'expectativasacas') THEN
    RAISE NOTICE '08 plantio_expectativa: pulado (coluna ja existe)';
    RETURN;
  END IF;

  ALTER TABLE tblplantio ADD COLUMN IF NOT EXISTS expectativasacas numeric(12,2) NULL; -- sacas esperadas (total)

  RAISE NOTICE '08 plantio_expectativa: aplicado';
END
$agro$;

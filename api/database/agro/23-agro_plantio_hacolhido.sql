-- =====================================================================
-- AGRO — Plantio: hectares colhidos (base da produtividade + projeção)
--
-- tblplantio.hacolhido = ha já colhidos do plantio. Com ele:
--   - produtividade REAL do talhão = colhido ÷ hacolhido (sc/ha)
--   - "finalizado" = hacolhido >= areaplantada (sem flag separado)
--   - PROJEÇÃO da produção (regra de 3) enquanto não finaliza:
--       colhido + (colhido ÷ hacolhido) × (areaplantada − hacolhido)
--     0 colhido → expectativa; finalizado → colhido.
--   - Disponível da safra = max(0, Σ produção dos plantios − contratado)
--   - média da colheita da safra = Σ colhido ÷ Σ hacolhido
--
-- Schema mgsis. Só ADD COLUMN (não-destrutivo, idempotente).
-- =====================================================================
--
-- GUARDA: pula se a coluna ja existe.
-- Bloco unico (atomico); sem BEGIN/COMMIT pra nao deixar transacao aberta no DBeaver.
-- =====================================================================

SET lock_timeout = '5s';

DO $agro$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblplantio' AND column_name = 'hacolhido') THEN
    RAISE NOTICE '23 plantio_hacolhido: pulado (coluna ja existe)';
    RETURN;
  END IF;

  ALTER TABLE tblplantio ADD COLUMN IF NOT EXISTS hacolhido numeric(12,4);

  RAISE NOTICE '23 plantio_hacolhido: aplicado';
END
$agro$;

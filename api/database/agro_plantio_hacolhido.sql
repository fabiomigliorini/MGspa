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

BEGIN;

ALTER TABLE tblplantio ADD COLUMN IF NOT EXISTS hacolhido numeric(12,4);

COMMIT;

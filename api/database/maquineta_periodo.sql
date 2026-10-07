-- =====================================================================
-- Maquineta e seus periodos (TASK-188 M9.8): o lote da maquineta vira o
-- periodo dela, no padrao do portador e seus periodos (doc-4).
--
--   tblmaquinetalote.fim — fim do periodo. Sem fim = aberto (recebe o
--                          cartao); com fim e sem fechamento = pendente
--                          (bordero digitado que nao bateu); com fechamento
--                          = conferido. `abertura` e' o inicio.
--
-- Idempotente. Transacional. Roda no go-live depois do conferencia.sql.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

ALTER TABLE tblmaquinetalote ADD COLUMN IF NOT EXISTS fim timestamp(0) without time zone;

-- lote conferido antes da coluna: termina no fechamento
UPDATE tblmaquinetalote SET fim = fechamento WHERE fim IS NULL AND fechamento IS NOT NULL;

-- o periodo aberto (o que recebe o cartao) e' o sem fim
DROP INDEX IF EXISTS idx_tblmaquinetalote_aberto;
CREATE INDEX IF NOT EXISTS idx_tblmaquinetalote_aberto
    ON tblmaquinetalote (codmaquineta) WHERE fim IS NULL;

COMMIT;

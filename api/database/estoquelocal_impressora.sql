-- =====================================================================
-- Impressora do local de estoque (TASK-55, etapa 2 do doc-6).
--
--   tblestoquelocal.codimpressora — onde sai sozinha a via de separacao da
--                   requisicao que este local recebe (termica ou A5, pelo
--                   tipo da impressora). Nulo = nao imprime sozinho.
--
-- SOMENTE ESTRUTURA (DDL). Roda depois do impressora.sql (FK).
-- Idempotente (IF NOT EXISTS + guard em pg_constraint). Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

ALTER TABLE tblestoquelocal ADD COLUMN IF NOT EXISTS codimpressora bigint;

CREATE INDEX IF NOT EXISTS idx_tblestoquelocal_codimpressora
    ON tblestoquelocal (codimpressora) WHERE codimpressora IS NOT NULL;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_tblestoquelocal_tblimpressora') THEN
        ALTER TABLE tblestoquelocal ADD CONSTRAINT fk_tblestoquelocal_tblimpressora
            FOREIGN KEY (codimpressora) REFERENCES tblimpressora (codimpressora) ON UPDATE CASCADE;
    END IF;
END $$;

COMMIT;

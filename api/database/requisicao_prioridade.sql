-- =====================================================================
-- Prioridade da requisicao (TASK-258).
--
--   tblrequisicao.prioridade — 1 Baixa, 2 Normal, 3 Alta, 4 Urgente. O
--                   numero ordena o painel sem `case`. Quem pede escolhe ao
--                   criar; depois so' muda pelo botao Prioridade do detalhe,
--                   com motivo (auditoria tipo 24).
--
-- SOMENTE ESTRUTURA (DDL). Roda depois do requisicao.sql.
-- Idempotente (IF NOT EXISTS + guard em pg_constraint). Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

ALTER TABLE tblrequisicao ADD COLUMN IF NOT EXISTS prioridade smallint NOT NULL DEFAULT 2;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_tblrequisicao_prioridade') THEN
        ALTER TABLE tblrequisicao ADD CONSTRAINT ck_tblrequisicao_prioridade
            CHECK (prioridade BETWEEN 1 AND 4);
    END IF;
END $$;

COMMIT;

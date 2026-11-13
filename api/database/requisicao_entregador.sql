-- =====================================================================
-- Entrega no endereco pelo entregador da loja que pede (TASK-55, #47-#50).
--
--   tblnegocioentrega.etapa — C retirado pelo entregador (a loja que atende
--                   entrega a mercadoria ao entregador da loja que pediu) /
--                   E entrega ao cliente (as linhas existentes). A retirada
--                   so' existe pela requisicao e sempre como "entregue".
--   tblrequisicao.status   — ganha R "Com o entregador" (destino E: todas as
--                   partes retiradas, falta entregar ao cliente).
--
-- SOMENTE ESTRUTURA (DDL). Depois do negocio_entrega_requisicao.sql.
-- Idempotente. Transacional. Roda ANTES do deploy do codigo.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

ALTER TABLE tblnegocioentrega ADD COLUMN IF NOT EXISTS etapa char(1) NOT NULL DEFAULT 'E';

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'tblnegocioentrega_etapa_check') THEN
        ALTER TABLE tblnegocioentrega ADD CONSTRAINT tblnegocioentrega_etapa_check
            CHECK (etapa IN ('C', 'E'));
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'tblnegocioentrega_retirada_check') THEN
        ALTER TABLE tblnegocioentrega ADD CONSTRAINT tblnegocioentrega_retirada_check
            CHECK (etapa = 'E' OR (codrequisicao IS NOT NULL AND resultado = 'E'));
    END IF;
    -- o check de status nasceu inline no requisicao.sql: recria com o R uma vez so'
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint
        WHERE conname = 'tblrequisicao_status_check'
        AND pg_get_constraintdef(oid) LIKE '%''R''%'
    ) THEN
        ALTER TABLE tblrequisicao DROP CONSTRAINT IF EXISTS tblrequisicao_status_check;
        ALTER TABLE tblrequisicao ADD CONSTRAINT tblrequisicao_status_check
            CHECK (status IN ('A', 'S', 'P', 'T', 'D', 'R', 'E', 'C'));
    END IF;
END $$;

COMMIT;

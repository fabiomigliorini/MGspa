-- =====================================================================
-- Entrega pela requisicao (TASK-55, etapa 10 do doc-6).
--
--   tblnegocioentrega.codrequisicao — a entrega registrada pela requisicao
--                   (retirada ou entrega ao cliente com o codigo de retirada
--                   de todas as requisicoes do negocio, ou justificativa sem
--                   ele; reposicao recebida pela loja que pediu). Codigo
--                   conferido e justificativa so' existem com requisicao.
--
-- SOMENTE ESTRUTURA (DDL). Depois do negocio_entrega.sql e do requisicao.sql.
-- Idempotente. Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

ALTER TABLE tblnegocioentrega ADD COLUMN IF NOT EXISTS codrequisicao bigint;

CREATE INDEX IF NOT EXISTS idx_tblnegocioentrega_codrequisicao
    ON tblnegocioentrega (codrequisicao) WHERE codrequisicao IS NOT NULL;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_tblnegocioentrega_tblrequisicao') THEN
        ALTER TABLE tblnegocioentrega ADD CONSTRAINT fk_tblnegocioentrega_tblrequisicao
            FOREIGN KEY (codrequisicao) REFERENCES tblrequisicao (codrequisicao) ON UPDATE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'tblnegocioentrega_requisicao_check') THEN
        ALTER TABLE tblnegocioentrega ADD CONSTRAINT tblnegocioentrega_requisicao_check
            CHECK ((NOT codigoconferido AND justificativa IS NULL) OR codrequisicao IS NOT NULL);
    END IF;
END $$;

COMMIT;

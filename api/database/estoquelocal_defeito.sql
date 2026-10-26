-- =====================================================================
-- Local de estoque de mercadoria com defeito (TASK-258).
--
--   tblestoquelocal.defeito — o local guarda a mercadoria com defeito,
--                   separada a espera de substituicao. Fica fora da
--                   requisicao entre lojas (painel, formulario e gerador).
--
-- Estrutura + marca o 199001 (Defeito). Roda antes do deploy.
-- Idempotente (IF NOT EXISTS). Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

ALTER TABLE tblestoquelocal ADD COLUMN IF NOT EXISTS defeito boolean NOT NULL DEFAULT false;

UPDATE tblestoquelocal SET defeito = true WHERE codestoquelocal = 199001 AND defeito = false;

COMMIT;

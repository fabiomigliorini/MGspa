-- =====================================================================
-- Cliente que retem o ISS (TASK-282).
--
--   tblpessoa.retemiss — o cliente e substituto tributario: retem o ISS
--                   do servico que contrata. Marcado no cadastro do
--                   cliente (app pessoas); a nota de servico (app notas)
--                   pre-seleciona a retencao ao escolher o tomador.
--
-- Somente estrutura: os clientes sao marcados a mao no cadastro.
-- Roda ANTES do deploy (o select de pessoa passa a ler a coluna).
-- Idempotente (IF NOT EXISTS). Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

ALTER TABLE tblpessoa ADD COLUMN IF NOT EXISTS retemiss boolean NOT NULL DEFAULT false;

COMMIT;

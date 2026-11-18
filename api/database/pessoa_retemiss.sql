-- =====================================================================
-- Cliente que retem o ISS (TASK-282).
--
--   tblpessoa.retemiss — o cliente e substituto tributario: retem o ISS
--                   do servico que contrata. Marcado no cadastro do
--                   cliente (app pessoas); a nota de servico (app notas)
--                   pre-seleciona a retencao ao escolher o tomador.
--
-- Estrutura + marca os clientes que ja retinham o ISS nas NFS-e que a FDF
-- emitiu pelo Coplan (historico no ADN de producao, lido em 18/11/2026).
-- Os demais sao marcados a mao no cadastro.
-- Roda ANTES do deploy (o select de pessoa passa a ler a coluna).
-- Idempotente (IF NOT EXISTS). Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

ALTER TABLE tblpessoa ADD COLUMN IF NOT EXISTS retemiss boolean NOT NULL DEFAULT false;

-- Tomadores com ISS retido (tpRetISSQN = 2) nas notas da FDF. Por CNPJ: a retencao e'
-- da empresa, vale para todo cadastro com o mesmo CNPJ.
--   04.576.775/0001-60  Migliorini & Migliorini Ltda      (24 notas retidas, IM 9552)
--   00.360.408/0001-74  Imobiliaria Irmaos Nogueira Ltda  (9 notas retidas, IM 5735)
UPDATE tblpessoa SET retemiss = true
WHERE cnpj IN (4576775000160, 360408000174) AND fisica = false AND retemiss = false;

COMMIT;

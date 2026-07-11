-- ============================================================
-- Agro: snapshot dos impostos travado na fixação de preço.
--
-- Antes: o preço LÍQUIDO da fixação era recalculado na leitura
-- (ContratoFixacaoResource) a partir da config de tributos (tblculturatributo)
-- + a competência da UPF na data — ou seja, o líquido mudava se a config/UPF
-- mudasse depois, e o operador não conseguia DIGITAR a alíquota/UPF efetiva.
--
-- Agora: ao criar/editar a fixação o operador informa (ou ajusta) as alíquotas
-- e a UPF no modal de impostos; o resultado é GRAVADO junto da fixação. O
-- líquido fica travado no momento da fixação (auditável, imune a mudança
-- posterior de config/UPF) — previsibilidade pro produtor.
--
--   precoliquido = R$/sc líquido travado (bruto − total das deduções)
--   totaldeducao = R$/sc somado das deduções
--   tributos     = snapshot JSON das linhas [{codtributo, codigo, descricao,
--                  base, percentual, upf, valor}] usadas no cálculo
--
-- Colunas NULL = fixação antiga / espelho automático do FIXO: o resource cai
-- no cálculo on-the-fly (retrocompatível).
--
-- Rodar no dev. PostgreSQL. Idempotente (ADD COLUMN IF NOT EXISTS).
-- ============================================================

BEGIN;

ALTER TABLE tblcontratofixacao
  ADD COLUMN IF NOT EXISTS precoliquido numeric NULL,
  ADD COLUMN IF NOT EXISTS totaldeducao numeric NULL,
  ADD COLUMN IF NOT EXISTS tributos jsonb NULL;

COMMENT ON COLUMN tblcontratofixacao.precoliquido IS
  'R$/sc líquido travado no momento da fixação (bruto − total das deduções). NULL = calcula on-the-fly.';
COMMENT ON COLUMN tblcontratofixacao.totaldeducao IS
  'R$/sc somado das deduções (FETHAB/IAGRO/SENAR/FUNRURAL) no momento da fixação.';
COMMENT ON COLUMN tblcontratofixacao.tributos IS
  'Snapshot JSON das linhas de imposto usadas no cálculo do líquido desta fixação.';

COMMIT;

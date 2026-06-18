-- =====================================================================
-- Contrato sem limite de carregamento (agro).
-- Flag para contratos que "levam o saldo do silo" (sobra de fim de safra):
-- nesses, o embarque NAO valida o teto do contratado (pula o bloqueio de
-- over-load). Default false = contrato com teto (limite no saldo a embarcar).
-- Reaplicavel (ADD COLUMN IF NOT EXISTS).
-- =====================================================================

BEGIN;

ALTER TABLE tblcontrato
  ADD COLUMN IF NOT EXISTS semlimite boolean NOT NULL DEFAULT false;

COMMENT ON COLUMN tblcontrato.semlimite IS
  'Contrato sem teto de carregamento (leva o saldo do silo); pula o bloqueio de over-load no embarque.';

COMMIT;

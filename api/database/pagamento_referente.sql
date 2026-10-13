-- =====================================================================
-- "Referente a" do pagamento (TASK-216).
--
-- Texto livre que sai no recibo e no vale ("Referente a"). A observacao
-- continua sendo coisa interna e nao sai no recibo. Preenchido no
-- lancamento (Receber/Pagar titulos, Vale/Adiantamento) e alteravel pelo
-- lapis do pagamento.
--
-- Idempotente. Transacional.
-- =====================================================================
BEGIN;

ALTER TABLE tblpagamento ADD COLUMN IF NOT EXISTS referente varchar(255);

COMMENT ON COLUMN tblpagamento.referente IS 'Referente a: texto livre que sai no recibo (a observacao e interna)';

COMMIT;

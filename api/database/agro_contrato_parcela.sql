-- =====================================================================
-- Fase 3 — Parcelas de pagamento do contrato (previsto x recebido).
-- Reusa as colunas existentes data/valor como o PREVISTO (data prevista /
-- valor previsto) e adiciona a confirmacao do RECEBIMENTO (pode divergir) +
-- modo (SACAS x VALOR), sacas e o portador que recebeu.
-- Reaplicavel.
-- =====================================================================

BEGIN;

ALTER TABLE tblcontratopagamento
  ADD COLUMN IF NOT EXISTS modo          varchar(10) NOT NULL DEFAULT 'VALOR', -- SACAS | VALOR
  ADD COLUMN IF NOT EXISTS sacas         numeric(14,3),
  ADD COLUMN IF NOT EXISTS datarecebido  date,
  ADD COLUMN IF NOT EXISTS valorrecebido numeric(14,2),
  ADD COLUMN IF NOT EXISTS codportador   integer REFERENCES tblportador(codportador);

ALTER TABLE tblcontratopagamento DROP CONSTRAINT IF EXISTS chk_contratopagamento_modo;
ALTER TABLE tblcontratopagamento
  ADD CONSTRAINT chk_contratopagamento_modo CHECK (modo IN ('SACAS', 'VALOR'));

COMMIT;

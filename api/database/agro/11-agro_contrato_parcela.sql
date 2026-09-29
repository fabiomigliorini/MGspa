-- =====================================================================
-- Fase 3 — Parcelas de pagamento do contrato (previsto x recebido).
-- Reusa as colunas existentes data/valor como o PREVISTO (data prevista /
-- valor previsto) e adiciona a confirmacao do RECEBIMENTO (pode divergir) +
-- modo (SACAS x VALOR), sacas e o portador que recebeu.
-- Reaplicavel.
-- =====================================================================
--
-- GUARDA: pula se ja aplicado ou se o passo 22 ja refatorou o pagamento (sem
-- codcontrato). Rodar este depois do 22 recriava datarecebido e re-armava o
-- DELETE do 22 — apagava todos os recebimentos.
-- Bloco unico (atomico); sem BEGIN/COMMIT pra nao deixar transacao aberta no DBeaver.
-- =====================================================================

SET lock_timeout = '5s';

DO $agro$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblcontratopagamento' AND column_name = 'modo') OR NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblcontratopagamento' AND column_name = 'codcontrato') THEN
    RAISE NOTICE '11 contrato_parcela: pulado (ja aplicado ou pagamento ja refatorado)';
    RETURN;
  END IF;

  ALTER TABLE tblcontratopagamento
    ADD COLUMN IF NOT EXISTS modo          varchar(10) NOT NULL DEFAULT 'VALOR', -- SACAS | VALOR
    ADD COLUMN IF NOT EXISTS sacas         numeric(14,3),
    ADD COLUMN IF NOT EXISTS datarecebido  date,
    ADD COLUMN IF NOT EXISTS valorrecebido numeric(14,2),
    ADD COLUMN IF NOT EXISTS codportador   integer REFERENCES tblportador(codportador);

  ALTER TABLE tblcontratopagamento DROP CONSTRAINT IF EXISTS chk_contratopagamento_modo;
  ALTER TABLE tblcontratopagamento
    ADD CONSTRAINT chk_contratopagamento_modo CHECK (modo IN ('SACAS', 'VALOR'));

  RAISE NOTICE '11 contrato_parcela: aplicado';
END
$agro$;

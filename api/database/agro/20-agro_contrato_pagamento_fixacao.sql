-- =====================================================================
-- Parcela de pagamento vinculada a UMA fixação + cotação na parcela.
--
-- Contexto: um contrato tem N fixações (cada uma em BRL ou US$) e cada
-- fixação pode ter N parcelas. Até aqui a parcela era só do CONTRATO — não
-- dava p/ dizer de qual fixação ela é. E a fixação em US$ é dolarizada pura
-- (precoreal NULL): o R$ nasce na PARCELA, pela cotação do dia do recebimento.
--
-- - codcontratofixacao: a fixação de origem da parcela (1 fixação : N parcelas).
--   ON DELETE RESTRICT preserva a trilha financeira (não deixa excluir uma
--   fixação que já tem parcela). NULL só p/ parcelas legadas ambíguas.
-- - cotacao: cotação USD->BRL PREVISTA (digitada ao criar a parcela). NULL em BRL.
-- - cotacaorecebido: cotação EFETIVA capturada no confirmar recebimento. NULL em BRL.
--
-- Backfill: parcelas de contrato com EXATAMENTE 1 fixação ativa recebem o
-- vínculo automaticamente; multi-fixação fica NULL (ambíguo, atribuição manual).
-- Reaplicável.
-- =====================================================================
--
-- GUARDA: pula se ja aplicado ou se o passo 22 ja refatorou o pagamento: o
-- backfill le codcontrato (falhava) e cotacao/cotacaorecebido voltariam orfas.
-- Bloco unico (atomico); sem BEGIN/COMMIT pra nao deixar transacao aberta no DBeaver.
-- =====================================================================

SET lock_timeout = '5s';

DO $agro$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblcontratopagamento' AND column_name = 'codcontratofixacao') OR NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblcontratopagamento' AND column_name = 'codcontrato') THEN
    RAISE NOTICE '20 pagamento_fixacao: pulado (ja aplicado ou pagamento ja refatorado)';
    RETURN;
  END IF;

  ALTER TABLE tblcontratopagamento
    ADD COLUMN IF NOT EXISTS codcontratofixacao integer NULL
      REFERENCES tblcontratofixacao(codcontratofixacao) ON DELETE RESTRICT,
    ADD COLUMN IF NOT EXISTS cotacao          numeric(10,4) NULL,
    ADD COLUMN IF NOT EXISTS cotacaorecebido  numeric(10,4) NULL;

  CREATE INDEX IF NOT EXISTS ix_contratopagamento_codcontratofixacao
    ON tblcontratopagamento (codcontratofixacao);

  -- Backfill só quando há UMA fixação ativa no contrato (vínculo não-ambíguo).
  UPDATE tblcontratopagamento p
     SET codcontratofixacao = (
          SELECT f.codcontratofixacao
            FROM tblcontratofixacao f
           WHERE f.codcontrato = p.codcontrato AND f.inativo IS NULL
         )
   WHERE p.codcontratofixacao IS NULL
     AND (
          SELECT count(*)
            FROM tblcontratofixacao f
           WHERE f.codcontrato = p.codcontrato AND f.inativo IS NULL
         ) = 1;

  RAISE NOTICE '20 pagamento_fixacao: aplicado';
END
$agro$;

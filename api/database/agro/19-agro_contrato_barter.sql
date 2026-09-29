-- =====================================================================
-- Contrato Barter — marcador explicito do contrato.
-- Um contrato barter e a troca de insumos por grao (settlement em insumos):
-- nao exige fixacao de preco nem parcelas de pagamento. Ate aqui "barter"
-- era derivado da existencia de uma parcela forma='BARTER' (circular: um
-- barter sem parcelas nunca era reconhecido). Esta coluna permite declarar
-- o contrato como barter direto no cabecalho; o tipo derivado passa a
-- respeita-la (ver ContratoResource::tipoDerivado). Reaplicavel.
-- =====================================================================
--
-- GUARDA: pula se a coluna ja existe.
-- Bloco unico (atomico); sem BEGIN/COMMIT pra nao deixar transacao aberta no DBeaver.
-- =====================================================================

SET lock_timeout = '5s';

DO $agro$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblcontrato' AND column_name = 'barter') THEN
    RAISE NOTICE '19 contrato_barter: pulado (coluna ja existe)';
    RETURN;
  END IF;

  ALTER TABLE tblcontrato
    ADD COLUMN IF NOT EXISTS barter boolean NOT NULL DEFAULT false;

  RAISE NOTICE '19 contrato_barter: aplicado';
END
$agro$;

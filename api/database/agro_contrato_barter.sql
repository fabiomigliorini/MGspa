-- =====================================================================
-- Contrato Barter — marcador explicito do contrato.
-- Um contrato barter e a troca de insumos por grao (settlement em insumos):
-- nao exige fixacao de preco nem parcelas de pagamento. Ate aqui "barter"
-- era derivado da existencia de uma parcela forma='BARTER' (circular: um
-- barter sem parcelas nunca era reconhecido). Esta coluna permite declarar
-- o contrato como barter direto no cabecalho; o tipo derivado passa a
-- respeita-la (ver ContratoResource::tipoDerivado). Reaplicavel.
-- =====================================================================

BEGIN;

ALTER TABLE tblcontrato
  ADD COLUMN IF NOT EXISTS barter boolean NOT NULL DEFAULT false;

COMMIT;

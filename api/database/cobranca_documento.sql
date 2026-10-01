-- =====================================================================
-- Cobranca integrada para documento que pode nao ser negocio (M5 do plano
-- doc-3, fechamento de caixa).
--
-- O wizard de cobranca do PDV deixou de ser do negocio: o PIX QR e os
-- pedidos PagarMe/Saurus sao criados para um documento (hoje o negocio; no
-- M7 o recebimento de titulos no balcao, sem negocio). tblpixcob e
-- tblpagarmepedido ja aceitam codnegocio nulo; falta tblsauruspedido.
--
-- Idempotente. Transacional. Pode rodar de novo a vontade.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '60s';

ALTER TABLE tblsauruspedido ALTER COLUMN codnegocio DROP NOT NULL;

-- Conferencia
SELECT table_name, column_name, is_nullable
FROM information_schema.columns
WHERE column_name = 'codnegocio'
  AND table_name IN ('tblpixcob', 'tblpagarmepedido', 'tblsauruspedido')
ORDER BY table_name;

COMMIT;

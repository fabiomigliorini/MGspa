-- =====================================================================
-- Cobranca integrada para documento que pode nao ser negocio (M5 do plano
-- doc-3, fechamento de caixa).
--
-- O wizard de cobranca do PDV deixou de ser do negocio: o PIX QR e os
-- pedidos PagarMe/Saurus sao criados para um documento (hoje o negocio; no
-- M7 o recebimento de titulos no balcao, sem negocio). tblpixcob e
-- tblpagarmepedido ja aceitam codnegocio nulo; falta tblsauruspedido.
--
-- O PDV antigo (forma de pagamento) sai junto: tblnegocioparcela perde
-- uuidforma, que so' servia para a copia do historico (pagamento.sql) e para
-- o sync do formato antigo. Roda depois do pagamento.sql (a view
-- tblnegocioformapagamento de la' ja' agrupa as parcelas por condicao).
--
-- Idempotente. Transacional. Pode rodar de novo a vontade.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '60s';

ALTER TABLE tblsauruspedido ALTER COLUMN codnegocio DROP NOT NULL;

DROP INDEX IF EXISTS idx_tblnegocioparcela_uuidforma;
ALTER TABLE tblnegocioparcela DROP COLUMN IF EXISTS uuidforma;

-- Conferencia
SELECT table_name, column_name, is_nullable
FROM information_schema.columns
WHERE column_name = 'codnegocio'
  AND table_name IN ('tblpixcob', 'tblpagarmepedido', 'tblsauruspedido')
ORDER BY table_name;

COMMIT;

-- =====================================================================
-- Maquineta e seus periodos (TASK-188 M9.8): o lote da maquineta vira o
-- periodo dela, no padrao do portador e seus periodos (doc-4).
--
--   tblmaquinetalote.fim — fim do periodo. Sem fim = aberto (recebe o
--                          cartao); com fim e sem fechamento = pendente
--                          (bordero digitado que nao bateu); com fechamento
--                          = conferido. `abertura` e' o inicio.
--   quantidade e total   — o bordero digitado e o sistema na conferencia
--                          (no lugar de credito e debito). O conferencia.sql
--                          ja' nasce assim; aqui acerta o banco que rodou a
--                          versao antiga.
--   tblpagamento.nsu e parcelas — copiados da Saurus e da PagarMe, que
--                          sempre mandaram e so' ficavam na integracao (a
--                          tela do periodo mostra o NSU do relatorio e
--                          separa credito a vista de parcelado).
--
-- Idempotente. Transacional. Roda no go-live depois do conferencia.sql.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '600s';

ALTER TABLE tblmaquinetalote ADD COLUMN IF NOT EXISTS fim timestamp(0) without time zone;

-- lote conferido antes da coluna: termina no fechamento
UPDATE tblmaquinetalote SET fim = fechamento WHERE fim IS NULL AND fechamento IS NOT NULL;

-- o periodo aberto (o que recebe o cartao) e' o sem fim
DROP INDEX IF EXISTS idx_tblmaquinetalote_aberto;
CREATE INDEX IF NOT EXISTS idx_tblmaquinetalote_aberto
    ON tblmaquinetalote (codmaquineta) WHERE fim IS NULL;

-- bordero: quantidade e total no lugar de credito e debito
ALTER TABLE tblmaquinetalote ADD COLUMN IF NOT EXISTS quantidadeinformada integer;
ALTER TABLE tblmaquinetalote ADD COLUMN IF NOT EXISTS totalinformado numeric(14,2);
ALTER TABLE tblmaquinetalote ADD COLUMN IF NOT EXISTS quantidadesistema integer;
ALTER TABLE tblmaquinetalote ADD COLUMN IF NOT EXISTS totalsistema numeric(14,2);
ALTER TABLE tblmaquinetalote DROP COLUMN IF EXISTS creditoinformado;
ALTER TABLE tblmaquinetalote DROP COLUMN IF EXISTS debitoinformado;
ALTER TABLE tblmaquinetalote DROP COLUMN IF EXISTS creditosistema;
ALTER TABLE tblmaquinetalote DROP COLUMN IF EXISTS debitosistema;

-- NSU e parcelas da Saurus (Safrapay): so' onde esta' vazio
WITH sp AS (
    SELECT DISTINCT ON (codsauruspedido) codsauruspedido, nsu
    FROM tblsauruspagamento
    ORDER BY codsauruspedido, codsauruspagamento DESC
)
UPDATE tblpagamento p
SET nsu = coalesce(p.nsu, sp.nsu),
    parcelas = coalesce(p.parcelas, ped.parcelas)
FROM tblsauruspedido ped
LEFT JOIN sp ON (sp.codsauruspedido = ped.codsauruspedido)
WHERE p.codsauruspedido = ped.codsauruspedido
AND ((p.nsu IS NULL AND sp.nsu IS NOT NULL) OR (p.parcelas IS NULL AND ped.parcelas IS NOT NULL));

-- NSU e parcelas da PagarMe (Stone): so' onde esta' vazio
WITH pp AS (
    SELECT DISTINCT ON (codpagarmepedido) codpagarmepedido, nsu
    FROM tblpagarmepagamento
    WHERE codpagarmepedido IS NOT NULL AND valorpagamento IS NOT NULL
    ORDER BY codpagarmepedido, codpagarmepagamento DESC
)
UPDATE tblpagamento p
SET nsu = coalesce(p.nsu, pp.nsu),
    parcelas = coalesce(p.parcelas, ped.parcelas)
FROM tblpagarmepedido ped
LEFT JOIN pp ON (pp.codpagarmepedido = ped.codpagarmepedido)
WHERE p.codpagarmepedido = ped.codpagarmepedido
AND ((p.nsu IS NULL AND pp.nsu IS NOT NULL) OR (p.parcelas IS NULL AND ped.parcelas IS NOT NULL));

COMMIT;

-- =====================================================================
-- Remove tblpdv.impressora (texto), substituida por tblpdv.codimpressora
-- (TASK-253, etapa 8 do doc-6). Antes do DROP repete a conversao pelo nome
-- (algum dispositivo pode ter gravado a impressora em texto entre o
-- pdv_impressora.sql e o deploy). Apagar tambem o api/printers.json do
-- servidor (arquivo local, fora do git).
--
-- ORDEM: depois da publicacao do codigo e da atualizacao dos PDVs.
-- Reaplicavel. Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'tblpdv' AND column_name = 'impressora'
    ) THEN
        UPDATE tblpdv p SET codimpressora = i.codimpressora
        FROM tblimpressora i
        WHERE i.impressora = p.impressora AND p.codimpressora IS NULL;
    END IF;
END $$;

ALTER TABLE tblpdv DROP COLUMN IF EXISTS impressora;

COMMIT;

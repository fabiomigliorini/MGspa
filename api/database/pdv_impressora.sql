-- =====================================================================
-- Impressora do dispositivo pelo cadastro (TASK-253, etapa 8 do doc-6).
--
--   tblpdv.codimpressora — substitui tblpdv.impressora (texto com o nome da
--                   fila no CUPS). A conversao e' pelo nome; o que nao casar
--                   aparece no SELECT do fim (cadastrar a impressora e rodar
--                   de novo, ou escolher em Meu Dispositivo).
--
-- ORDEM: no go-live, depois do impressora.sql + `php artisan
-- impressora:importar-json`, ANTES da publicacao do codigo que le o
-- codimpressora. tblpdv.impressora sai no impressora_drop.sql, depois.
-- Idempotente. Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

ALTER TABLE tblpdv ADD COLUMN IF NOT EXISTS codimpressora bigint;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_tblpdv_tblimpressora') THEN
        ALTER TABLE tblpdv ADD CONSTRAINT fk_tblpdv_tblimpressora
            FOREIGN KEY (codimpressora) REFERENCES tblimpressora (codimpressora) ON UPDATE CASCADE;
    END IF;
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'tblpdv' AND column_name = 'impressora'
    ) THEN
        UPDATE tblpdv p SET codimpressora = i.codimpressora
        FROM tblimpressora i
        WHERE i.impressora = p.impressora AND p.codimpressora IS NULL;
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_tblpdv_codimpressora
    ON tblpdv (codimpressora) WHERE codimpressora IS NOT NULL;

-- conferencia: dispositivos com impressora em texto que nao casou com o cadastro
DO $$
DECLARE
    r record;
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'tblpdv' AND column_name = 'impressora'
    ) THEN
        FOR r IN EXECUTE 'SELECT codpdv, apelido, impressora FROM tblpdv
            WHERE impressora IS NOT NULL AND impressora <> '''' AND codimpressora IS NULL' LOOP
            RAISE NOTICE 'Sem impressora no cadastro: PDV % (%) — %', r.codpdv, r.apelido, r.impressora;
        END LOOP;
    END IF;
END $$;

COMMIT;

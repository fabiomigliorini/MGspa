-- =====================================================================
-- Substituicao de NFS-e (TASK-279 #5).
--
--   tblnotafiscalservico.codnotafiscalservicosubstituida — a NFS-e
--       autorizada que esta nota substitui. A DPS leva a chave dela
--       (subst/chSubstda); autorizada a substituta, o Sistema Nacional
--       cancela a anterior (evento 105102).
--   tblnotafiscalservico.substituicaomotivo — subst/cMotivo: 1, 2, 3, 4,
--       5 ou 99 (na DPS com dois digitos).
--   tblnotafiscalservico.substituicaojustificativa — subst/xMotivo,
--       opcional (quando vem, de 15 a 255 caracteres).
--
-- Rodar ANTES do deploy do api: o detalhe da nota carrega as substitutas
-- pela coluna nova.
--
-- Idempotente (IF NOT EXISTS). Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

ALTER TABLE tblnotafiscalservico ADD COLUMN IF NOT EXISTS codnotafiscalservicosubstituida bigint;
ALTER TABLE tblnotafiscalservico ADD COLUMN IF NOT EXISTS substituicaomotivo smallint;
ALTER TABLE tblnotafiscalservico ADD COLUMN IF NOT EXISTS substituicaojustificativa varchar(255);

CREATE INDEX IF NOT EXISTS idx_tblnotafiscalservico_codnotafiscalservicosubstituida
    ON tblnotafiscalservico (codnotafiscalservicosubstituida);

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_tblnotafiscalservico_tblnotafiscalservico') THEN
        ALTER TABLE tblnotafiscalservico ADD CONSTRAINT fk_tblnotafiscalservico_tblnotafiscalservico
            FOREIGN KEY (codnotafiscalservicosubstituida)
            REFERENCES tblnotafiscalservico (codnotafiscalservico) ON UPDATE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'tblnotafiscalservico_substituicaomotivo_check') THEN
        ALTER TABLE tblnotafiscalservico ADD CONSTRAINT tblnotafiscalservico_substituicaomotivo_check
            CHECK (substituicaomotivo IN (1, 2, 3, 4, 5, 99));
    END IF;
END $$;

COMMIT;

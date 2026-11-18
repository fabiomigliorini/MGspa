-- =====================================================================
-- Percentuais do Simples por mes, no cadastro da filial (TASK-280).
--
--   tblfilialsimples — os dois percentuais que a nota de servico (NFS-e
--                      Nacional, TASK-279) pede e que mudam todo mes com o
--                      faturamento dos ultimos 12 meses do Simples. Informados
--                      uma vez por mes na filial (app pessoas); a nota puxa
--                      pela filial e pelo mes da competencia.
--
--     competencia        sempre o 1o dia do mes (a API normaliza)
--     tributospercentual % aproximado total dos tributos do Simples
--                        (pTotTribSN da DPS, obrigatorio para ME/EPP — E0712)
--     issaliquota        aliquota efetiva do ISS no Simples (so vai na DPS
--                        com ISS retido — E0625)
--
-- Os tipos sao os mesmos das colunas da tblnotafiscalservico.
--
-- Rodar ANTES do deploy: o Duplicar da nota de servico ja le a tabela.
--
-- Idempotente (IF NOT EXISTS + guards em pg_constraint). Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

CREATE TABLE IF NOT EXISTS tblfilialsimples (
    codfilialsimples bigserial NOT NULL,
    codfilial bigint NOT NULL,
    -- 1o dia do mes
    competencia date NOT NULL,
    tributospercentual numeric(4,2) NOT NULL,
    issaliquota numeric(5,2) NOT NULL,
    criacao timestamp(0) without time zone DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) without time zone DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT pk_tblfilialsimples PRIMARY KEY (codfilialsimples)
);

CREATE UNIQUE INDEX IF NOT EXISTS uk_tblfilialsimples_codfilial_competencia
    ON tblfilialsimples (codfilial, competencia);

DO $$
DECLARE
    fk record;
BEGIN
    FOR fk IN
        SELECT * FROM (VALUES
            ('fk_tblfilialsimples_tblfilial', 'codfilial', 'tblfilial', 'codfilial'),
            ('fk_tblfilialsimples_tblusuario', 'codusuariocriacao', 'tblusuario', 'codusuario'),
            ('fk_tblfilialsimples_tblusuario_0', 'codusuarioalteracao', 'tblusuario', 'codusuario')
        ) AS t(nome, coluna, tabela, referencia)
    LOOP
        IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = fk.nome) THEN
            EXECUTE format(
                'ALTER TABLE tblfilialsimples ADD CONSTRAINT %I FOREIGN KEY (%I) '
                'REFERENCES %I (%I) ON UPDATE CASCADE',
                fk.nome, fk.coluna, fk.tabela, fk.referencia
            );
        END IF;
    END LOOP;
END $$;

COMMIT;

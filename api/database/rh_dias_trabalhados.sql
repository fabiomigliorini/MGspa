-- =====================================================================
-- Dias trabalhados de volta nas rubricas do RH (TASK-262).
--
--   tblcolaboradorrubrica.diastrabalhados — dias que a pessoa trabalhou no
--                   setor da rubrica (quem cobre alguns dias num xerox).
--                   Paga percentual x dias / dias uteis. Nulo = periodo inteiro.
--   tblperiodocolaborador.faltas — faltas do colaborador no periodo,
--                   informadas no acerto. Nulo = sem faltas.
--   tblrubrica.descontaabsenteismo — a rubrica do catalogo desconta as
--                   faltas: valor x (dias uteis - faltas) / dias uteis.
--                   Nulo = nao desconta.
--
-- O rh_rubricas.sql (29/07/2026) tinha eliminado dias trabalhados e o
-- desconto de absenteismo; aqui voltam, sem a tabela de vinculo.
--
-- SOMENTE ESTRUTURA (DDL). Todas nulas: nada muda de valor ate o RH marcar.
-- Idempotente (IF NOT EXISTS + guard em pg_constraint). Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

ALTER TABLE tblcolaboradorrubrica ADD COLUMN IF NOT EXISTS diastrabalhados smallint;
ALTER TABLE tblperiodocolaborador ADD COLUMN IF NOT EXISTS faltas smallint;
ALTER TABLE tblrubrica ADD COLUMN IF NOT EXISTS descontaabsenteismo boolean;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_tblcolaboradorrubrica_diastrabalhados') THEN
        ALTER TABLE tblcolaboradorrubrica ADD CONSTRAINT ck_tblcolaboradorrubrica_diastrabalhados
            CHECK (diastrabalhados >= 0);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_tblperiodocolaborador_faltas') THEN
        ALTER TABLE tblperiodocolaborador ADD CONSTRAINT ck_tblperiodocolaborador_faltas
            CHECK (faltas >= 0);
    END IF;
END $$;

COMMIT;

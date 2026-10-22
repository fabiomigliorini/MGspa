-- =====================================================================
-- Cadastro de impressoras (TASK-253, etapa 1 do doc-6).
--
--   tblimpressora — substitui o api/printers.json (arquivo local, fora do
--                   git). impressora = nome da fila no CUPS, que e' tambem o
--                   nome do evento no canal "printing" do Ably que o MGprint
--                   assina; descricao = o que aparece na tela; tipo T termica
--                   80 mm, A A5, E etiqueta.
--
-- SOMENTE ESTRUTURA (DDL). As impressoras do printers.json de cada ambiente
-- entram pelo comando `php artisan impressora:importar-json` (le o arquivo
-- do servidor; nao duplica). Rodar o comando ANTES de apagar o arquivo.
--
-- Idempotente (IF NOT EXISTS + guards em pg_constraint). Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

CREATE TABLE IF NOT EXISTS tblimpressora (
    codimpressora bigserial NOT NULL,
    -- nome da fila no CUPS = evento do Ably (vai para o `lp -d` do MGprint)
    impressora varchar(100) NOT NULL,
    descricao varchar(100) NOT NULL,
    -- T termica 80 mm, A A5, E etiqueta
    tipo char(1) NOT NULL DEFAULT 'T',
    inativo timestamp(0) without time zone,
    criacao timestamp(0) without time zone DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) without time zone DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT pk_tblimpressora PRIMARY KEY (codimpressora),
    CONSTRAINT tblimpressora_impressora_check CHECK (impressora ~ '^[A-Za-z0-9_.-]+$'),
    CONSTRAINT tblimpressora_tipo_check CHECK (tipo IN ('T', 'A', 'E'))
);

CREATE UNIQUE INDEX IF NOT EXISTS uk_tblimpressora_impressora
    ON tblimpressora (impressora);

DO $$
DECLARE
    fk record;
BEGIN
    FOR fk IN
        SELECT * FROM (VALUES
            ('fk_tblimpressora_tblusuario', 'codusuariocriacao'),
            ('fk_tblimpressora_tblusuario_0', 'codusuarioalteracao')
        ) AS t(nome, coluna)
    LOOP
        IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = fk.nome) THEN
            EXECUTE format(
                'ALTER TABLE tblimpressora ADD CONSTRAINT %I FOREIGN KEY (%I) '
                'REFERENCES tblusuario (codusuario) ON UPDATE CASCADE',
                fk.nome, fk.coluna
            );
        END IF;
    END LOOP;
END $$;

COMMIT;

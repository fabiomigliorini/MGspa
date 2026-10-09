-- =====================================================================
-- Trilha da data alterada no movimento do portador (TASK-204): o ajuste,
-- a transferencia, o item e o bordero da maquineta de parceiro mudam de
-- data (e de periodo) com justificativa; o antes/depois fica aqui, como a
-- tblpagamentocorrecao faz com o pagamento.
--
-- Idempotente (IF NOT EXISTS + guards em pg_constraint). Transacional.
-- Roda no go-live depois do conferencia.sql.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

CREATE TABLE IF NOT EXISTS tblportadormovimentocorrecao (
    codportadormovimentocorrecao bigserial NOT NULL,
    codportadormovimento bigint NOT NULL,
    antes jsonb NOT NULL,
    depois jsonb NOT NULL,
    justificativa varchar(300) NOT NULL,
    criacao timestamp(0) without time zone DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) without time zone DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT pk_tblportadormovimentocorrecao PRIMARY KEY (codportadormovimentocorrecao)
);
CREATE INDEX IF NOT EXISTS idx_tblportadormovimentocorrecao_codportadormovimento
    ON tblportadormovimentocorrecao (codportadormovimento);

DO $$
DECLARE fk record;
BEGIN
    FOR fk IN
        SELECT * FROM (VALUES
            ('fk_tblportadormovimentocorrecao_tblportadormovimento', 'tblportadormovimentocorrecao', 'codportadormovimento', 'tblportadormovimento', 'codportadormovimento'),
            ('fk_tblportadormovimentocorrecao_tblusuario', 'tblportadormovimentocorrecao', 'codusuariocriacao', 'tblusuario', 'codusuario'),
            ('fk_tblportadormovimentocorrecao_tblusuario_0', 'tblportadormovimentocorrecao', 'codusuarioalteracao', 'tblusuario', 'codusuario')
        ) AS t(nome, tabela, coluna, ref, refcoluna)
    LOOP
        IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = fk.nome) THEN
            EXECUTE format(
                'ALTER TABLE %I ADD CONSTRAINT %I FOREIGN KEY (%I) REFERENCES %I (%I) ON UPDATE CASCADE',
                fk.tabela, fk.nome, fk.coluna, fk.ref, fk.refcoluna
            );
        END IF;
    END LOOP;
END $$;

COMMIT;

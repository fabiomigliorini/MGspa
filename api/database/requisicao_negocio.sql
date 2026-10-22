-- =====================================================================
-- Negocio de separacao ligado a requisicao (TASK-55, etapa 9 do doc-6).
--
--   tblnegociorequisicao — as requisicoes que um negocio de transferencia do
--                   PDV atende (um negocio pode atender varias, do mesmo
--                   destino da mercadoria). O PDV monta a lista offline (QR,
--                   numero digitado ou o Separar da requisicao) e sincroniza
--                   junto com o negocio, pelo uuid. inativo = tirada do negocio.
--                   O negocio nao calcula nada: o processo da requisicao le
--                   as linhas dos negocios ligados e grava o atendido nela.
--
-- SOMENTE ESTRUTURA (DDL). Idempotente. Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

CREATE TABLE IF NOT EXISTS tblnegociorequisicao (
    codnegociorequisicao bigserial NOT NULL,
    codnegocio bigint NOT NULL,
    codrequisicao bigint NOT NULL,
    uuid uuid NOT NULL DEFAULT gen_random_uuid(),
    inativo timestamp(0) without time zone,
    criacao timestamp(0) without time zone DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) without time zone DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT pk_tblnegociorequisicao PRIMARY KEY (codnegociorequisicao)
);

CREATE UNIQUE INDEX IF NOT EXISTS uk_tblnegociorequisicao_uuid
    ON tblnegociorequisicao (uuid);
-- a mesma requisicao uma vez so' (ativa) em cada negocio
CREATE UNIQUE INDEX IF NOT EXISTS uk_tblnegociorequisicao_ativa
    ON tblnegociorequisicao (codnegocio, codrequisicao) WHERE inativo IS NULL;
CREATE INDEX IF NOT EXISTS idx_tblnegociorequisicao_codrequisicao
    ON tblnegociorequisicao (codrequisicao);

DO $$
DECLARE
    fk record;
BEGIN
    FOR fk IN
        SELECT * FROM (VALUES
            ('fk_tblnegociorequisicao_tblnegocio', 'codnegocio', 'tblnegocio', 'codnegocio'),
            ('fk_tblnegociorequisicao_tblrequisicao', 'codrequisicao', 'tblrequisicao', 'codrequisicao'),
            ('fk_tblnegociorequisicao_tblusuario', 'codusuariocriacao', 'tblusuario', 'codusuario'),
            ('fk_tblnegociorequisicao_tblusuario_0', 'codusuarioalteracao', 'tblusuario', 'codusuario')
        ) AS t(nome, coluna, ref, refcoluna)
    LOOP
        IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = fk.nome) THEN
            EXECUTE format(
                'ALTER TABLE tblnegociorequisicao ADD CONSTRAINT %I FOREIGN KEY (%I) '
                'REFERENCES %I (%I) ON UPDATE CASCADE',
                fk.nome, fk.coluna, fk.ref, fk.refcoluna
            );
        END IF;
    END LOOP;
END $$;

COMMIT;

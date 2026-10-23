-- =====================================================================
-- Entregas do negocio (TASK-55, etapa 7 do doc-6).
--
--   tblnegocioentrega — cada tentativa de entrega de um negocio: resultado
--                       E entregue, P entregue com problema, N nao entregue;
--                       motivo conforme o resultado; quem recebeu; observacao.
--                       codigoconferido/justificativa: a entrega pela
--                       requisicao (etapa 10) com ou sem o codigo de retirada.
--                       inativo = registro estornado.
--   tblnegocio.entrega  — (ja' existe) tem entrega a controlar.
--   tblnegocio.entregue — data da entrega efetiva (gravada pelo servico ao
--                       registrar E/P; limpa ao estornar a ultima). As listas
--                       "Entregas a fazer" e "A receber" usam indices parciais
--                       so' dos pendentes.
--
-- SOMENTE ESTRUTURA (DDL). tblnegocio tem milhoes de linhas: a coluna nasce
-- sem default (so' metadado) e os indices sao criados CONCURRENTLY, fora da
-- transacao. Idempotente.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

ALTER TABLE tblnegocio ADD COLUMN IF NOT EXISTS entregue timestamp(0) without time zone;

CREATE TABLE IF NOT EXISTS tblnegocioentrega (
    codnegocioentrega bigserial NOT NULL,
    codnegocio bigint NOT NULL,
    data timestamp(0) without time zone NOT NULL DEFAULT now(),
    -- E entregue, P entregue com problema, N nao entregue
    resultado char(1) NOT NULL,
    -- com problema: E mercadoria errada, A avariada, F faltando item;
    -- nao entregue: U cliente ausente, R recusou, L endereco nao encontrado
    motivo char(1),
    recebedor varchar(100),
    observacao varchar(500),
    -- entrega pela requisicao (etapa 10): conferida pelo codigo de retirada ou,
    -- sem ele, com justificativa
    codigoconferido boolean NOT NULL DEFAULT false,
    justificativa varchar(200),
    inativo timestamp(0) without time zone,
    criacao timestamp(0) without time zone DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) without time zone DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT pk_tblnegocioentrega PRIMARY KEY (codnegocioentrega),
    CONSTRAINT tblnegocioentrega_resultado_check CHECK (resultado IN ('E', 'P', 'N')),
    CONSTRAINT tblnegocioentrega_motivo_check CHECK (
        (resultado = 'E' AND motivo IS NULL)
        OR (resultado = 'P' AND motivo IN ('E', 'A', 'F'))
        OR (resultado = 'N' AND motivo IN ('U', 'R', 'L'))
    ),
    CONSTRAINT tblnegocioentrega_recebedor_check CHECK (resultado = 'N' OR recebedor IS NOT NULL)
);

CREATE INDEX IF NOT EXISTS idx_tblnegocioentrega_codnegocio
    ON tblnegocioentrega (codnegocio);

DO $$
DECLARE
    fk record;
BEGIN
    FOR fk IN
        SELECT * FROM (VALUES
            ('fk_tblnegocioentrega_tblnegocio', 'codnegocio', 'tblnegocio', 'codnegocio'),
            ('fk_tblnegocioentrega_tblusuario', 'codusuariocriacao', 'tblusuario', 'codusuario'),
            ('fk_tblnegocioentrega_tblusuario_0', 'codusuarioalteracao', 'tblusuario', 'codusuario')
        ) AS t(nome, coluna, ref, refcoluna)
    LOOP
        IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = fk.nome) THEN
            EXECUTE format(
                'ALTER TABLE tblnegocioentrega ADD CONSTRAINT %I FOREIGN KEY (%I) '
                'REFERENCES %I (%I) ON UPDATE CASCADE',
                fk.nome, fk.coluna, fk.ref, fk.refcoluna
            );
        END IF;
    END LOOP;
END $$;

COMMIT;

-- fora da transacao: so' os negocios com entrega pendente
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_tblnegocio_entrega_pendente_origem
    ON tblnegocio (codestoquelocal) WHERE entrega AND entregue IS NULL;
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_tblnegocio_entrega_pendente_destino
    ON tblnegocio (codestoquelocaldestino) WHERE entrega AND entregue IS NULL;

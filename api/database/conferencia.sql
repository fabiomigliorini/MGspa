-- =====================================================================
-- Conferencias e fechamento do caixa (M9 do plano doc-3, redesenhado em
-- 02/10/2026).
--
-- SOMENTE ESTRUTURA (DDL). Nenhum dado historico e' tocado: as
-- conferencias comecam no go-live (CONFERENCIA_INICIO no .env da API).
--
--   tblportadorperiodo     — sessao da gaveta: o caixa abre e fecha com
--                            contagem no PDV; o gerente confere (as cegas)
--   tblmaquinetalote       — o bordero da maquineta: um aberto por
--                            maquineta; o gerente fecha digitando quantidade
--                            e total do bordero (com foto)
--   tblpagamento           — lote do cartao, lote do cancelamento, registro
--                            indevido, sessao da gaveta do dinheiro e
--                            conferencia item a item (cheque, vale)
--
-- Idempotente (IF NOT EXISTS + guards em pg_constraint). Transacional.
-- Roda antes do tipo_titulo_limpeza.sql (o ultimo do go-live).
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

-- ADD COLUMN em tblpagamento (sem DEFAULT volatil, nao reescreve) pega
-- ACCESS EXCLUSIVE por milissegundos; com transacao ociosa segurando lock o
-- script desiste em 5s e faz rollback. Nunca remover estes dois SET.
SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

-- ---------------------------------------------------------------------
-- 1. Sessao da gaveta (periodo do portador; modelo do M10)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tblportadorperiodo (
    codportadorperiodo bigserial NOT NULL,
    codportador bigint NOT NULL,
    inicio timestamp(0) without time zone NOT NULL,
    fim timestamp(0) without time zone,
    fechamento timestamp(0) without time zone,
    codusuarioabertura bigint,
    codusuariofechamento bigint,
    saldoinicial numeric(14,2) NOT NULL DEFAULT 0,
    saldofinal numeric(14,2),
    moedasabertura numeric(14,2),
    cedulasabertura numeric(14,2),
    moedasfechamento numeric(14,2),
    cedulasfechamento numeric(14,2),
    conferencia timestamp(0) without time zone,
    codusuarioconferencia bigint,
    valorconferido numeric(14,2),
    observacoes varchar(500),
    criacao timestamp(0) without time zone DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) without time zone DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT pk_tblportadorperiodo PRIMARY KEY (codportadorperiodo)
);

-- um corrente (fim nulo) por portador
CREATE UNIQUE INDEX IF NOT EXISTS uk_tblportadorperiodo_corrente
    ON tblportadorperiodo (codportador) WHERE fim IS NULL;
CREATE INDEX IF NOT EXISTS idx_tblportadorperiodo_codportador_inicio
    ON tblportadorperiodo (codportador, inicio);
CREATE INDEX IF NOT EXISTS idx_tblportadorperiodo_pendente
    ON tblportadorperiodo (codportador) WHERE conferencia IS NULL;

-- ---------------------------------------------------------------------
-- 2. Lote da maquineta (o bordero)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tblmaquinetalote (
    codmaquinetalote bigserial NOT NULL,
    codmaquineta bigint NOT NULL,
    abertura timestamp(0) without time zone NOT NULL DEFAULT now(),
    fechamento timestamp(0) without time zone,
    codusuariofechamento bigint,
    quantidadeinformada integer,
    totalinformado numeric(14,2),
    quantidadesistema integer,
    totalsistema numeric(14,2),
    observacoes varchar(500),
    criacao timestamp(0) without time zone DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) without time zone DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT pk_tblmaquinetalote PRIMARY KEY (codmaquinetalote)
);

-- o lote corrente e' o mais novo da maquineta, se aberto; reabrir um lote
-- antigo deixa dois abertos (o antigo so' recebe lancamento movido)
CREATE INDEX IF NOT EXISTS idx_tblmaquinetalote_aberto
    ON tblmaquinetalote (codmaquineta) WHERE fechamento IS NULL;
CREATE INDEX IF NOT EXISTS idx_tblmaquinetalote_codmaquineta_abertura
    ON tblmaquinetalote (codmaquineta, abertura);

-- ---------------------------------------------------------------------
-- 3. Colunas novas do pagamento
-- ---------------------------------------------------------------------
ALTER TABLE tblpagamento ADD COLUMN IF NOT EXISTS codmaquinetalote bigint;
ALTER TABLE tblpagamento ADD COLUMN IF NOT EXISTS codmaquinetalotecancelamento bigint;
ALTER TABLE tblpagamento ADD COLUMN IF NOT EXISTS indevido boolean NOT NULL DEFAULT false;
ALTER TABLE tblpagamento ADD COLUMN IF NOT EXISTS codportadorperiodo bigint;
ALTER TABLE tblpagamento ADD COLUMN IF NOT EXISTS conferencia timestamp(0) without time zone;
ALTER TABLE tblpagamento ADD COLUMN IF NOT EXISTS codusuarioconferencia bigint;

CREATE INDEX IF NOT EXISTS idx_tblpagamento_codmaquinetalote
    ON tblpagamento (codmaquinetalote) WHERE codmaquinetalote IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_tblpagamento_codmaquinetalotecancelamento
    ON tblpagamento (codmaquinetalotecancelamento) WHERE codmaquinetalotecancelamento IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_tblpagamento_codportadorperiodo
    ON tblpagamento (codportadorperiodo) WHERE codportadorperiodo IS NOT NULL;

-- ---------------------------------------------------------------------
-- 4. Acerto da venda: removido (10/10/2026). Venda nao fica com
--    diferenca: ou e' pagamento ou e' duplicata.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS tblnegocioacerto;

-- ---------------------------------------------------------------------
-- 5. Chaves estrangeiras
-- ---------------------------------------------------------------------
DO $$
DECLARE
    fk record;
BEGIN
    FOR fk IN
        SELECT * FROM (VALUES
            ('fk_tblportadorperiodo_tblportador', 'tblportadorperiodo', 'codportador', 'tblportador', 'codportador'),
            ('fk_tblportadorperiodo_tblusuario_abertura', 'tblportadorperiodo', 'codusuarioabertura', 'tblusuario', 'codusuario'),
            ('fk_tblportadorperiodo_tblusuario_fechamento', 'tblportadorperiodo', 'codusuariofechamento', 'tblusuario', 'codusuario'),
            ('fk_tblportadorperiodo_tblusuario_conferencia', 'tblportadorperiodo', 'codusuarioconferencia', 'tblusuario', 'codusuario'),
            ('fk_tblportadorperiodo_tblusuario', 'tblportadorperiodo', 'codusuariocriacao', 'tblusuario', 'codusuario'),
            ('fk_tblportadorperiodo_tblusuario_0', 'tblportadorperiodo', 'codusuarioalteracao', 'tblusuario', 'codusuario'),
            ('fk_tblmaquinetalote_tblmaquineta', 'tblmaquinetalote', 'codmaquineta', 'tblmaquineta', 'codmaquineta'),
            ('fk_tblmaquinetalote_tblusuario_fechamento', 'tblmaquinetalote', 'codusuariofechamento', 'tblusuario', 'codusuario'),
            ('fk_tblmaquinetalote_tblusuario', 'tblmaquinetalote', 'codusuariocriacao', 'tblusuario', 'codusuario'),
            ('fk_tblmaquinetalote_tblusuario_0', 'tblmaquinetalote', 'codusuarioalteracao', 'tblusuario', 'codusuario'),
            ('fk_tblpagamento_tblmaquinetalote', 'tblpagamento', 'codmaquinetalote', 'tblmaquinetalote', 'codmaquinetalote'),
            ('fk_tblpagamento_tblmaquinetalote_cancelamento', 'tblpagamento', 'codmaquinetalotecancelamento', 'tblmaquinetalote', 'codmaquinetalote'),
            ('fk_tblpagamento_tblportadorperiodo', 'tblpagamento', 'codportadorperiodo', 'tblportadorperiodo', 'codportadorperiodo'),
            ('fk_tblpagamento_tblusuario_conferencia', 'tblpagamento', 'codusuarioconferencia', 'tblusuario', 'codusuario')
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

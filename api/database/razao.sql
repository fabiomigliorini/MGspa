-- =====================================================================
-- Razao do dinheiro e `transacao` = fato gerador (M10 do plano doc-3).
--
-- SOMENTE ESTRUTURA (DDL). Nenhum historico e' copiado: o razao comeca no
-- go-live (CONFERENCIA_INICIO no .env da API, o mesmo corte do M9).
--
--   1. lancamento -> transacao (data e hora do fato gerador; a hora em
--      que foi digitado e' a criacao) em tblpagamento, tblcheque,
--      tblextratobancario e tblbonificacaoevento. A view
--      tblliquidacaotitulo acompanha sozinha (ja expoe `transacao`).
--   2. tblportadormovimento recriada (estava vazia, com as colunas do
--      desenho antigo): toda linha nasce de um pagamento.
--   3. tblportadortransferencia (vazia) cai: a transferencia e' pagamento
--      com origem e destino (decisao 2).
--
-- Idempotente (rodar de novo nao faz nada). Transacional. Roda depois do
-- conferencia.sql e antes do tipo_titulo_limpeza.sql (o ultimo do go-live).
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

-- RENAME e DROP pegam ACCESS EXCLUSIVE por milissegundos; com transacao
-- ociosa segurando lock o script desiste em 5s e faz rollback. Nunca
-- remover estes dois SET.
SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

-- ---------------------------------------------------------------------
-- 1. lancamento -> transacao (e os indices com o nome antigo)
-- ---------------------------------------------------------------------
DO $$
DECLARE
    t text;
BEGIN
    FOREACH t IN ARRAY ARRAY['tblpagamento', 'tblcheque', 'tblextratobancario', 'tblbonificacaoevento']
    LOOP
        IF EXISTS (
            SELECT 1 FROM information_schema.columns
            WHERE table_schema = current_schema() AND table_name = t AND column_name = 'lancamento'
        ) THEN
            EXECUTE format('ALTER TABLE %I RENAME COLUMN lancamento TO transacao', t);
        END IF;
    END LOOP;
END $$;

ALTER INDEX IF EXISTS idx_tblpagamento_estado_lancamento
    RENAME TO idx_tblpagamento_estado_transacao;
ALTER INDEX IF EXISTS idx_tblpagamento_codmaquineta_lancamento
    RENAME TO idx_tblpagamento_codmaquineta_transacao;

-- ---------------------------------------------------------------------
-- 2. Razao: tblportadormovimento recriada
-- ---------------------------------------------------------------------
DO $$
DECLARE
    qtd bigint;
BEGIN
    -- ainda no desenho antigo (coluna lancamento)?
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = current_schema() AND table_name = 'tblportadormovimento'
        AND column_name = 'lancamento'
    ) THEN
        EXECUTE 'SELECT count(*) FROM tblportadormovimento' INTO qtd;
        IF qtd > 0 THEN
            RAISE EXCEPTION 'tblportadormovimento antiga tem % linhas: confira antes de recriar', qtd;
        END IF;
        EXECUTE 'SELECT count(*) FROM tblextratobancarioportadormovimento' INTO qtd;
        IF qtd > 0 THEN
            RAISE EXCEPTION 'tblextratobancarioportadormovimento tem % linhas: confira antes de recriar', qtd;
        END IF;
        ALTER TABLE tblextratobancarioportadormovimento
            DROP CONSTRAINT IF EXISTS fk_tblextratobancarioportadormovimento_tblportadormovimento;
        DROP TABLE tblportadormovimento;
    END IF;
END $$;

CREATE TABLE IF NOT EXISTS tblportadormovimento (
    codportadormovimento bigserial NOT NULL,
    codportador bigint NOT NULL,
    codportadorperiodo bigint NOT NULL,
    codpagamento bigint NOT NULL,
    -- com sinal: positivo = entrou no portador, negativo = saiu
    valor numeric(14,2) NOT NULL,
    -- quando o dinheiro aparece neste portador (meio imediato = a
    -- transacao do pagamento; cartao, no M14, + o prazo da parcela)
    transacao timestamp(0) without time zone NOT NULL,
    -- parcela do cartao (M14); nulo nos meios imediatos
    parcela smallint,
    conciliado boolean NOT NULL DEFAULT false,
    inativo timestamp(0) without time zone,
    criacao timestamp(0) without time zone DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) without time zone DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT pk_tblportadormovimento PRIMARY KEY (codportadormovimento),
    CONSTRAINT tblportadormovimento_valor_check CHECK (valor <> 0)
);

CREATE INDEX IF NOT EXISTS idx_tblportadormovimento_codpagamento
    ON tblportadormovimento (codpagamento);
CREATE INDEX IF NOT EXISTS idx_tblportadormovimento_codportador_transacao
    ON tblportadormovimento (codportador, transacao) WHERE inativo IS NULL;
CREATE INDEX IF NOT EXISTS idx_tblportadormovimento_codportadorperiodo
    ON tblportadormovimento (codportadorperiodo);
-- uma linha ativa por pagamento, portador e parcela
CREATE UNIQUE INDEX IF NOT EXISTS uk_tblportadormovimento_ativo
    ON tblportadormovimento (codpagamento, codportador, coalesce(parcela, 0)) WHERE inativo IS NULL;

-- ---------------------------------------------------------------------
-- 3. tblportadortransferencia cai (vazia)
-- ---------------------------------------------------------------------
DO $$
DECLARE
    qtd bigint;
BEGIN
    IF to_regclass('tblportadortransferencia') IS NOT NULL THEN
        EXECUTE 'SELECT count(*) FROM tblportadortransferencia' INTO qtd;
        IF qtd > 0 THEN
            RAISE EXCEPTION 'tblportadortransferencia tem % linhas: confira antes de apagar', qtd;
        END IF;
        DROP TABLE tblportadortransferencia;
    END IF;
END $$;

-- ---------------------------------------------------------------------
-- 4. Chaves estrangeiras
-- ---------------------------------------------------------------------
DO $$
DECLARE
    fk record;
BEGIN
    FOR fk IN
        SELECT * FROM (VALUES
            ('fk_tblportadormovimento_tblportador', 'tblportadormovimento', 'codportador', 'tblportador', 'codportador'),
            ('fk_tblportadormovimento_tblportadorperiodo', 'tblportadormovimento', 'codportadorperiodo', 'tblportadorperiodo', 'codportadorperiodo'),
            ('fk_tblportadormovimento_tblpagamento', 'tblportadormovimento', 'codpagamento', 'tblpagamento', 'codpagamento'),
            ('fk_tblportadormovimento_tblusuario', 'tblportadormovimento', 'codusuariocriacao', 'tblusuario', 'codusuario'),
            ('fk_tblportadormovimento_tblusuario_0', 'tblportadormovimento', 'codusuarioalteracao', 'tblusuario', 'codusuario'),
            ('fk_tblextratobancarioportadormovimento_tblportadormovimento', 'tblextratobancarioportadormovimento', 'codportadormovimento', 'tblportadormovimento', 'codportadormovimento')
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

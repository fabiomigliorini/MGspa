-- =====================================================================
-- Itens do caixa, ajuste de caixa e contagem por cedula/moeda (M13 do
-- plano doc-3).
--
-- SOMENTE ESTRUTURA (DDL) + os 6 itens iniciais. Nenhum dado historico e'
-- tocado.
--
--   tblcaixaitem             — mercadoria de parceiro fora do fiscal que
--                              passa pela gaveta: modo C contagem (chips,
--                              ingressos impressos) ou M maquineta/terceiro
--                              (Bilhete Agora, BlackTicket, Redeflex,
--                              Bradesco Expresso). Pessoa e conta contabil
--                              nascem nulas: o Fabio preenche no contas
--   tblcaixaitemlancamento   — o item em cada sessao da gaveta (abertura,
--                              entrada, saida, vendido, fechamento), o
--                              pagamento entrada - saida na gaveta e o
--                              titulo de repasse gerado no fechamento
--   tblpagamento             — codcaixaitemlancamento (documento: item)
--   tblportadorperiodo       — codpagamentoabertura / codpagamentofechamento
--                              (ajuste de caixa da sessao, decisao 21) e
--                              contagemabertura / contagemfechamento
--                              (quantidade de cada cedula e moeda, jsonb)
--
-- Idempotente (IF NOT EXISTS + guards em pg_constraint). Transacional.
-- Roda depois do razao.sql e antes do tipo_titulo_limpeza.sql (o ultimo
-- do go-live).
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

-- ADD COLUMN em tblpagamento (sem DEFAULT volatil, nao reescreve) pega
-- ACCESS EXCLUSIVE por milissegundos; com transacao ociosa segurando lock o
-- script desiste em 5s e faz rollback. Nunca remover estes dois SET.
SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

-- ---------------------------------------------------------------------
-- 1. Cadastro dos itens
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tblcaixaitem (
    codcaixaitem bigserial NOT NULL,
    item varchar(50) NOT NULL,
    modo char(1) NOT NULL,
    codfilial bigint,
    codpessoa bigint,
    codcontacontabil bigint,
    ordem smallint NOT NULL DEFAULT 0,
    inativo timestamp(0) without time zone,
    criacao timestamp(0) without time zone DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) without time zone DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT pk_tblcaixaitem PRIMARY KEY (codcaixaitem),
    CONSTRAINT tblcaixaitem_modo_check CHECK (modo IN ('C', 'M')),
    -- o titulo de repasse exige conta contabil
    CONSTRAINT tblcaixaitem_conta_check CHECK (codpessoa IS NULL OR codcontacontabil IS NOT NULL)
);

-- os 6 itens do formulario "Movimento do Caixa" (todas as filiais)
INSERT INTO tblcaixaitem (item, modo, ordem)
SELECT v.item, v.modo, v.ordem
FROM (VALUES
    ('Chips de celular', 'C', 10),
    ('Ingressos impressos', 'C', 20),
    ('Bilhete Agora', 'M', 30),
    ('BlackTicket', 'M', 40),
    ('Redeflex recarga', 'M', 50),
    ('Bradesco Expresso', 'M', 60)
) AS v(item, modo, ordem)
WHERE NOT EXISTS (SELECT 1 FROM tblcaixaitem i WHERE i.item = v.item);

-- ---------------------------------------------------------------------
-- 2. O item em cada sessao da gaveta
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tblcaixaitemlancamento (
    codcaixaitemlancamento bigserial NOT NULL,
    codportadorperiodo bigint NOT NULL,
    codcaixaitem bigint NOT NULL,
    valorabertura numeric(14,2),
    valorfechamento numeric(14,2),
    valorvendido numeric(14,2),
    valorentrada numeric(14,2) NOT NULL DEFAULT 0,
    valorsaida numeric(14,2) NOT NULL DEFAULT 0,
    observacoes varchar(300),
    codpagamento bigint,
    codtitulo bigint,
    criacao timestamp(0) without time zone DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) without time zone DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT pk_tblcaixaitemlancamento PRIMARY KEY (codcaixaitemlancamento),
    CONSTRAINT tblcaixaitemlancamento_valores_check CHECK (
        coalesce(valorabertura, 0) >= 0
        AND coalesce(valorfechamento, 0) >= 0
        AND coalesce(valorvendido, 0) >= 0
        AND valorentrada >= 0
        AND valorsaida >= 0
    )
);

CREATE UNIQUE INDEX IF NOT EXISTS uk_tblcaixaitemlancamento_periodo_item
    ON tblcaixaitemlancamento (codportadorperiodo, codcaixaitem);
CREATE INDEX IF NOT EXISTS idx_tblcaixaitemlancamento_codcaixaitem
    ON tblcaixaitemlancamento (codcaixaitem);

-- ---------------------------------------------------------------------
-- 3. Pagamento do item; ajuste e contagem da sessao
-- ---------------------------------------------------------------------
ALTER TABLE tblpagamento ADD COLUMN IF NOT EXISTS codcaixaitemlancamento bigint;
CREATE INDEX IF NOT EXISTS idx_tblpagamento_codcaixaitemlancamento
    ON tblpagamento (codcaixaitemlancamento) WHERE codcaixaitemlancamento IS NOT NULL;

ALTER TABLE tblportadorperiodo ADD COLUMN IF NOT EXISTS codpagamentoabertura bigint;
ALTER TABLE tblportadorperiodo ADD COLUMN IF NOT EXISTS codpagamentofechamento bigint;
ALTER TABLE tblportadorperiodo ADD COLUMN IF NOT EXISTS contagemabertura jsonb;
ALTER TABLE tblportadorperiodo ADD COLUMN IF NOT EXISTS contagemfechamento jsonb;

-- ---------------------------------------------------------------------
-- 4. Chaves estrangeiras
-- ---------------------------------------------------------------------
DO $$
DECLARE
    fk record;
BEGIN
    FOR fk IN
        SELECT * FROM (VALUES
            ('fk_tblcaixaitem_tblfilial', 'tblcaixaitem', 'codfilial', 'tblfilial', 'codfilial'),
            ('fk_tblcaixaitem_tblpessoa', 'tblcaixaitem', 'codpessoa', 'tblpessoa', 'codpessoa'),
            ('fk_tblcaixaitem_tblcontacontabil', 'tblcaixaitem', 'codcontacontabil', 'tblcontacontabil', 'codcontacontabil'),
            ('fk_tblcaixaitem_tblusuario', 'tblcaixaitem', 'codusuariocriacao', 'tblusuario', 'codusuario'),
            ('fk_tblcaixaitem_tblusuario_0', 'tblcaixaitem', 'codusuarioalteracao', 'tblusuario', 'codusuario'),
            ('fk_tblcaixaitemlancamento_tblportadorperiodo', 'tblcaixaitemlancamento', 'codportadorperiodo', 'tblportadorperiodo', 'codportadorperiodo'),
            ('fk_tblcaixaitemlancamento_tblcaixaitem', 'tblcaixaitemlancamento', 'codcaixaitem', 'tblcaixaitem', 'codcaixaitem'),
            ('fk_tblcaixaitemlancamento_tblpagamento', 'tblcaixaitemlancamento', 'codpagamento', 'tblpagamento', 'codpagamento'),
            ('fk_tblcaixaitemlancamento_tbltitulo', 'tblcaixaitemlancamento', 'codtitulo', 'tbltitulo', 'codtitulo'),
            ('fk_tblcaixaitemlancamento_tblusuario', 'tblcaixaitemlancamento', 'codusuariocriacao', 'tblusuario', 'codusuario'),
            ('fk_tblcaixaitemlancamento_tblusuario_0', 'tblcaixaitemlancamento', 'codusuarioalteracao', 'tblusuario', 'codusuario'),
            ('fk_tblpagamento_tblcaixaitemlancamento', 'tblpagamento', 'codcaixaitemlancamento', 'tblcaixaitemlancamento', 'codcaixaitemlancamento'),
            ('fk_tblportadorperiodo_tblpagamento_abertura', 'tblportadorperiodo', 'codpagamentoabertura', 'tblpagamento', 'codpagamento'),
            ('fk_tblportadorperiodo_tblpagamento_fechamento', 'tblportadorperiodo', 'codpagamentofechamento', 'tblpagamento', 'codpagamento')
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

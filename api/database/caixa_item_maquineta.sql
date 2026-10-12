-- =====================================================================
-- Maquineta de parceiro como item do caixa (doc-4, "Itens de parceiro";
-- TASK-39 #20 e #22): Bilhete Agora, Redeflex, Rede Card.
--
-- O parceiro deixa a maquineta na loja; cartao e Pix vao direto para ele, so'
-- o dinheiro passa pela gaveta. Cada maquineta e' um item do caixa no modo M:
-- nao tem estoque nem entra na contagem; o caixa lanca o total em dinheiro do
-- bordero do dia (tipo M no movimento do portador, com a foto no disco). O
-- financeiro acompanha a conta corrente da maquineta (o que devemos ao
-- parceiro) e paga gerando o titulo a pagar.
--
--   tblcaixaitem          — modo C (conta como cedula: chips) ou M
--                           (maquineta de parceiro: pessoa, filial e conta
--                           contabil do titulo).
--   tblportadormovimento  — tipo M (bordero da maquineta): codcaixaitem, sem
--                           `itens`; estado E feito ou C cancelado.
--   tblcaixaitemacerto    — debitos e ajustes da conta corrente da maquineta:
--                           T titulo gerado (valor negativo, com codtitulo) e
--                           A ajuste (com sinal e observacao). Credito sao os
--                           borderos (tipo M).
--
-- Idempotente. Transacional. Roda no go-live depois do
-- caixa_item_dinamico.sql.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

-- ---------------------------------------------------------------------
-- 1. Modo do item e os dados do titulo da maquineta
-- ---------------------------------------------------------------------
ALTER TABLE tblcaixaitem
    ADD COLUMN IF NOT EXISTS modo character(1) NOT NULL DEFAULT 'C',
    ADD COLUMN IF NOT EXISTS codpessoa bigint,
    ADD COLUMN IF NOT EXISTS codfilial bigint,
    ADD COLUMN IF NOT EXISTS codcontacontabil bigint;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_tblcaixaitem_tblpessoa') THEN
        ALTER TABLE tblcaixaitem ADD CONSTRAINT fk_tblcaixaitem_tblpessoa
            FOREIGN KEY (codpessoa) REFERENCES tblpessoa (codpessoa) ON UPDATE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_tblcaixaitem_tblfilial') THEN
        ALTER TABLE tblcaixaitem ADD CONSTRAINT fk_tblcaixaitem_tblfilial
            FOREIGN KEY (codfilial) REFERENCES tblfilial (codfilial) ON UPDATE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_tblcaixaitem_tblcontacontabil') THEN
        ALTER TABLE tblcaixaitem ADD CONSTRAINT fk_tblcaixaitem_tblcontacontabil
            FOREIGN KEY (codcontacontabil) REFERENCES tblcontacontabil (codcontacontabil) ON UPDATE CASCADE;
    END IF;
END $$;

ALTER TABLE tblcaixaitem DROP CONSTRAINT IF EXISTS tblcaixaitem_modo_check;
ALTER TABLE tblcaixaitem ADD CONSTRAINT tblcaixaitem_modo_check CHECK (
    (modo = 'C' AND codpessoa IS NULL AND codfilial IS NULL AND codcontacontabil IS NULL)
    OR (modo = 'M' AND codpessoa IS NOT NULL AND codfilial IS NOT NULL AND codcontacontabil IS NOT NULL)
);

-- ---------------------------------------------------------------------
-- 2. Tipo M (bordero da maquineta) no movimento do portador
-- ---------------------------------------------------------------------
ALTER TABLE tblportadormovimento DROP CONSTRAINT IF EXISTS tblportadormovimento_tipo_check;
ALTER TABLE tblportadormovimento ADD CONSTRAINT tblportadormovimento_tipo_check CHECK (
    tipo = ANY (ARRAY['P'::bpchar, 'A'::bpchar, 'T'::bpchar, 'I'::bpchar, 'M'::bpchar])
    AND (tipo = 'P') = (codpagamento IS NOT NULL)
    AND (tipo = 'P') = (estado IS NULL)
    AND (estado IS NULL OR estado = ANY (ARRAY['P'::bpchar, 'E'::bpchar, 'C'::bpchar]))
    AND (tipo = 'P' OR inativo IS NULL)
    AND (tipo IN ('I', 'M')) = (codcaixaitem IS NOT NULL)
    AND (tipo = 'I') = (itens IS NOT NULL)
    AND (tipo NOT IN ('I', 'M') OR estado = ANY (ARRAY['E'::bpchar, 'C'::bpchar]))
);

-- ---------------------------------------------------------------------
-- 3. Conta corrente da maquineta: titulos gerados e ajustes
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tblcaixaitemacerto (
    codcaixaitemacerto bigserial NOT NULL,
    codcaixaitem bigint NOT NULL,
    tipo character(1) NOT NULL,
    valor numeric(14,2) NOT NULL,
    codtitulo bigint,
    transacao timestamp(0) without time zone NOT NULL,
    observacoes character varying(300),
    cancelamento timestamp(0) without time zone,
    codusuariocancelamento bigint,
    justificativa character varying(300),
    criacao timestamp(0) without time zone DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) without time zone DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT pk_tblcaixaitemacerto PRIMARY KEY (codcaixaitemacerto),
    CONSTRAINT tblcaixaitemacerto_tipo_check CHECK (
        tipo = ANY (ARRAY['T'::bpchar, 'A'::bpchar])
        AND valor <> 0
        AND (tipo = 'T') = (codtitulo IS NOT NULL)
        AND (tipo <> 'T' OR valor < 0)
        AND (tipo <> 'A' OR observacoes IS NOT NULL)
        AND (cancelamento IS NULL) = (justificativa IS NULL)
    ),
    CONSTRAINT fk_tblcaixaitemacerto_tblcaixaitem FOREIGN KEY (codcaixaitem) REFERENCES tblcaixaitem(codcaixaitem) ON UPDATE CASCADE,
    CONSTRAINT fk_tblcaixaitemacerto_tbltitulo FOREIGN KEY (codtitulo) REFERENCES tbltitulo(codtitulo) ON UPDATE CASCADE,
    CONSTRAINT fk_tblcaixaitemacerto_usuariocancelamento FOREIGN KEY (codusuariocancelamento) REFERENCES tblusuario(codusuario) ON UPDATE CASCADE,
    CONSTRAINT fk_tblcaixaitemacerto_usuariocriacao FOREIGN KEY (codusuariocriacao) REFERENCES tblusuario(codusuario) ON UPDATE CASCADE,
    CONSTRAINT fk_tblcaixaitemacerto_usuarioalteracao FOREIGN KEY (codusuarioalteracao) REFERENCES tblusuario(codusuario) ON UPDATE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_tblcaixaitemacerto_codcaixaitem ON tblcaixaitemacerto (codcaixaitem, transacao);
CREATE INDEX IF NOT EXISTS idx_tblcaixaitemacerto_codtitulo ON tblcaixaitemacerto (codtitulo) WHERE codtitulo IS NOT NULL;

COMMIT;

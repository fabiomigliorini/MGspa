-- =====================================================================
-- Itens do caixa (doc-4, "Itens do caixa"; TASK-39): chips, ingressos.
--
-- O item conta como cedula: o saldo do portador em especie inclui os itens
-- (a valor de face), a contagem e' cedulas + moedas + itens e vender nao
-- lanca nada. O unico lancamento do item e' a entrada (com sinal), uma linha
-- do tipo novo I no movimento do portador.
--
--   tblportadormovimento   — tipo I (item): codcaixaitem e as linhas da
--                            entrada em `itens` (jsonb [{preco, quantidade,
--                            descricao}]); estado E feito ou C cancelado,
--                            como o ajuste. Nao vira tblpagamento.
--   tblportadorperiodo     — contagemitensinicial / contagemitensfinal (jsonb
--                            {codcaixaitem: [{preco, quantidade, descricao}]}),
--                            ao lado da contagem das cedulas e moedas.
--   tblcaixaitem           — cadastro minimo (item, inativo): saem modo,
--                            codfilial, codpessoa, codcontacontabil e ordem
--                            (voltam quando o item que precisar chegar) e os 5
--                            itens que nao sao chip.
--   tblcaixaitemlancamento e tblpagamento.codcaixaitemlancamento — saem (o
--                            item do M13). Recusa se houver pagamento ou
--                            lancamento de item mexido.
--
-- Idempotente. Transacional. Roda no go-live depois do
-- portador_movimento_tipo.sql e antes do tipo_titulo_limpeza.sql.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

-- ---------------------------------------------------------------------
-- 1. Tipo I no movimento do portador
-- ---------------------------------------------------------------------
ALTER TABLE tblportadormovimento
    ADD COLUMN IF NOT EXISTS codcaixaitem bigint,
    ADD COLUMN IF NOT EXISTS itens jsonb;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_tblportadormovimento_tblcaixaitem') THEN
        ALTER TABLE tblportadormovimento ADD CONSTRAINT fk_tblportadormovimento_tblcaixaitem
            FOREIGN KEY (codcaixaitem) REFERENCES tblcaixaitem (codcaixaitem) ON UPDATE CASCADE;
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_tblportadormovimento_codcaixaitem
    ON tblportadormovimento (codcaixaitem) WHERE codcaixaitem IS NOT NULL;

ALTER TABLE tblportadormovimento DROP CONSTRAINT IF EXISTS tblportadormovimento_tipo_check;
ALTER TABLE tblportadormovimento ADD CONSTRAINT tblportadormovimento_tipo_check CHECK (
    tipo = ANY (ARRAY['P'::bpchar, 'A'::bpchar, 'T'::bpchar, 'I'::bpchar])
    AND (tipo = 'P') = (codpagamento IS NOT NULL)
    AND (tipo = 'P') = (estado IS NULL)
    AND (estado IS NULL OR estado = ANY (ARRAY['P'::bpchar, 'E'::bpchar, 'C'::bpchar]))
    AND (tipo = 'P' OR inativo IS NULL)
    AND (tipo = 'I') = (codcaixaitem IS NOT NULL)
    AND (tipo = 'I') = (itens IS NOT NULL)
    AND (tipo <> 'I' OR estado = ANY (ARRAY['E'::bpchar, 'C'::bpchar]))
);

-- ---------------------------------------------------------------------
-- 2. Contagem dos itens no periodo
-- ---------------------------------------------------------------------
ALTER TABLE tblportadorperiodo
    ADD COLUMN IF NOT EXISTS contagemitensinicial jsonb,
    ADD COLUMN IF NOT EXISTS contagemitensfinal jsonb;

-- ---------------------------------------------------------------------
-- 3. O item do M13 sai (so' se nada foi usado)
-- ---------------------------------------------------------------------
DO $$
BEGIN
    IF to_regclass('tblcaixaitemlancamento') IS NOT NULL THEN
        IF EXISTS (
            SELECT 1 FROM tblcaixaitemlancamento
            WHERE codpagamento IS NOT NULL
            OR codtitulo IS NOT NULL
            OR valorentrada <> 0
            OR valorsaida <> 0
            OR coalesce(valorvendido, 0) <> 0
            OR coalesce(valorabertura, 0) <> 0
            OR coalesce(valorfechamento, 0) <> 0
        ) THEN
            RAISE EXCEPTION 'tblcaixaitemlancamento tem item movimentado: migrar a mao antes.';
        END IF;
    END IF;
END $$;

ALTER TABLE tblpagamento DROP CONSTRAINT IF EXISTS fk_tblpagamento_tblcaixaitemlancamento;
DROP INDEX IF EXISTS idx_tblpagamento_codcaixaitemlancamento;
ALTER TABLE tblpagamento DROP COLUMN IF EXISTS codcaixaitemlancamento;
DROP TABLE IF EXISTS tblcaixaitemlancamento;

-- ---------------------------------------------------------------------
-- 4. Cadastro minimo: so' o nome; dos 6 itens iniciais fica so' o chip
-- ---------------------------------------------------------------------
ALTER TABLE tblcaixaitem DROP CONSTRAINT IF EXISTS tblcaixaitem_modo_check;
ALTER TABLE tblcaixaitem DROP CONSTRAINT IF EXISTS tblcaixaitem_conta_check;
ALTER TABLE tblcaixaitem DROP CONSTRAINT IF EXISTS fk_tblcaixaitem_tblpessoa;
ALTER TABLE tblcaixaitem DROP CONSTRAINT IF EXISTS fk_tblcaixaitem_tblcontacontabil;
ALTER TABLE tblcaixaitem DROP CONSTRAINT IF EXISTS fk_tblcaixaitem_tblfilial;
ALTER TABLE tblcaixaitem
    DROP COLUMN IF EXISTS modo,
    DROP COLUMN IF EXISTS codfilial,
    DROP COLUMN IF EXISTS codpessoa,
    DROP COLUMN IF EXISTS codcontacontabil,
    DROP COLUMN IF EXISTS ordem;

DELETE FROM tblcaixaitem i
WHERE i.item IN ('Ingressos impressos', 'Bilhete Agora', 'BlackTicket', 'Redeflex recarga', 'Bradesco Expresso')
AND NOT EXISTS (SELECT 1 FROM tblportadormovimento m WHERE m.codcaixaitem = i.codcaixaitem);

COMMIT;

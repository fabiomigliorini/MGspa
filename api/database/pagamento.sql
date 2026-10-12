-- =====================================================================
-- Pagamento e parcelas do negocio (M4 do plano doc-3, fechamento de caixa).
--
-- Secao 1 (M4): tblpagamento e tblnegocioparcela no lugar de
-- tblnegocioformapagamento.
--
--   tblpagamento: o ato de o dinheiro se mover (venda, e depois titulo,
--     transferencia, ajuste...). Valor sempre positivo; o sentido vem de
--     origem/destino (nulo = fora da empresa). Colunas de valor iguais as do
--     movimento de titulo: principal, juros, multa, desconto e
--     total = principal + juros + multa - desconto (o dinheiro que ficou);
--     valortroco a parte (a NF-e manda vPag = total + troco).
--       meio    1 dinheiro, 2 cheque, 3 credito, 4 debito, 12 vale,
--               15 boleto, 16 deposito, 17 PIX, 18 transferencia,
--               99 outros; internos 91 compensacao, 92 folha, 93 permuta,
--               94 perda
--       estado  P pendente, E efetivado, C cancelado
--       motivo  so sem documento: T taxa, F tarifa, R rendimento,
--               A ajuste de caixa
--       codtitulo  vale consumido como pagamento (meio 12)
--
--   tblnegocioparcela: o que a venda deixou para depois; vira titulo ao
--     fechar (tbltitulo.codnegocioparcela).
--       condicao  F fechamento, P parcelado, B boleto, E entrega,
--                 X PIX/deposito a receber, V vale da devolucao
--       uuidforma  forma antiga que gerou a parcela: so' para a copia do
--                  historico; o cobranca_documento.sql (M5) derruba a coluna
--
-- Historico (mantendo os codigos: pagamento 123 = antiga forma 123):
--   - forma com titulo, ou a prazo, ou vale da devolucao -> uma parcela por
--     titulo (vencimento e valor do titulo); sem titulo, uma parcela com o
--     valor da forma;
--   - demais -> pagamento (estado pelo negocio: aberto P, fechado E,
--     cancelado C; meio pelo tipo, ou pela forma/pedido PagarMe; cartao sem
--     tipo = 99 outros);
--   - formas de valor zero nao sao copiadas; cartao negativo (estorno lancado
--     na propria venda) vira pagamento contrario (origem = portador da
--     adquirente, codpagamentoorigem = cartao da mesma autorizacao);
--   - troco lancado em dobro (2024-2025, troco maior que o pago): o troco em
--     dinheiro do negocio e' redistribuido como o PDV faz hoje (maior
--     pagamento primeiro), fechando com o total da venda.
--   tbltitulo -> codnegocioparcela; tblmovimentotitulo (vale usado) ->
--   codpagamento; tblcheque -> codpagamento; tblportadormovimento (vazia,
--   recriada no M10) perde a coluna.
--
-- A tabela antiga e vwnegocioformapagamento caem (vwnegocioformapagamentototais
-- fica, redefinida, para vwnegocio/vwnegocio_listagem); no lugar fica a VIEW
-- tblnegocioformapagamento (pagamentos + parcelas agrupadas pela forma,
-- colunas antigas, forma deduzida de meio/condicao) para o Totais de Caixa
-- do MG Lara, ate sair. tblformapagamento fica congelada.
--
-- Idempotente. Transacional. Pode rodar de novo a vontade.
-- =====================================================================

\set ON_ERROR_STOP on

-- ---------------------------------------------------------------------
-- 1. Estrutura
-- ---------------------------------------------------------------------
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '10min';

CREATE TABLE IF NOT EXISTS tblpagamento (
    codpagamento bigserial NOT NULL,
    uuid uuid NOT NULL DEFAULT gen_random_uuid(),
    codportadororigem bigint,
    codportadordestino bigint,
    meio smallint NOT NULL,
    estado char(1) NOT NULL DEFAULT 'P',
    principal numeric(14,2) NOT NULL,
    juros numeric(14,2) NOT NULL DEFAULT 0,
    multa numeric(14,2) NOT NULL DEFAULT 0,
    desconto numeric(14,2) NOT NULL DEFAULT 0,
    total numeric(14,2) NOT NULL,
    valortroco numeric(14,2),
    parcelas smallint,
    lancamento timestamp(0) without time zone NOT NULL DEFAULT now(),
    efetivacao timestamp(0) without time zone,
    codusuarioefetivacao bigint,
    cancelamento timestamp(0) without time zone,
    codusuariocancelamento bigint,
    justificativa varchar(300),
    codpessoa bigint,
    codpdv bigint,
    codfilial bigint,
    motivo char(1),
    codnegocio bigint,
    codpagamentoorigem bigint,
    codmaquineta bigint,
    bandeira smallint,
    autorizacao varchar(40),
    nsu varchar(20),
    codpixcob bigint,
    codpix bigint,
    codpagarmepedido bigint,
    codsauruspedido bigint,
    codliopedido bigint,
    codtitulo bigint,
    cmc7 varchar(50),
    chequevencimento date,
    chequecnpj numeric(14,0),
    chequeemitente varchar(100),
    observacoes varchar(300),
    criacao timestamp(0) without time zone DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) without time zone DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT pk_tblpagamento PRIMARY KEY (codpagamento)
);

CREATE TABLE IF NOT EXISTS tblnegocioparcela (
    codnegocioparcela bigserial NOT NULL,
    uuid uuid NOT NULL DEFAULT gen_random_uuid(),
    codnegocio bigint NOT NULL,
    condicao char(1) NOT NULL,
    numero smallint NOT NULL DEFAULT 1,
    vencimento date NOT NULL,
    valor numeric(14,2) NOT NULL,
    juros numeric(14,2) NOT NULL DEFAULT 0,
    codtitulo bigint,
    uuidforma uuid,
    criacao timestamp(0) without time zone DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) without time zone DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT pk_tblnegocioparcela PRIMARY KEY (codnegocioparcela)
);

DO $$
DECLARE
    r record;
BEGIN
    FOR r IN SELECT * FROM (VALUES
        ('fk_tblpagamento_tblportador_origem', 'tblpagamento', 'codportadororigem', 'tblportador(codportador)'),
        ('fk_tblpagamento_tblportador_destino', 'tblpagamento', 'codportadordestino', 'tblportador(codportador)'),
        ('fk_tblpagamento_tblusuario_efetivacao', 'tblpagamento', 'codusuarioefetivacao', 'tblusuario(codusuario)'),
        ('fk_tblpagamento_tblusuario_cancelamento', 'tblpagamento', 'codusuariocancelamento', 'tblusuario(codusuario)'),
        ('fk_tblpagamento_tblpessoa', 'tblpagamento', 'codpessoa', 'tblpessoa(codpessoa)'),
        ('fk_tblpagamento_tblpdv', 'tblpagamento', 'codpdv', 'tblpdv(codpdv)'),
        ('fk_tblpagamento_tblfilial', 'tblpagamento', 'codfilial', 'tblfilial(codfilial)'),
        ('fk_tblpagamento_tblnegocio', 'tblpagamento', 'codnegocio', 'tblnegocio(codnegocio)'),
        ('fk_tblpagamento_tblpagamento_origem', 'tblpagamento', 'codpagamentoorigem', 'tblpagamento(codpagamento)'),
        ('fk_tblpagamento_tblmaquineta', 'tblpagamento', 'codmaquineta', 'tblmaquineta(codmaquineta)'),
        ('fk_tblpagamento_tblpixcob', 'tblpagamento', 'codpixcob', 'tblpixcob(codpixcob)'),
        ('fk_tblpagamento_tblpix', 'tblpagamento', 'codpix', 'tblpix(codpix)'),
        ('fk_tblpagamento_tblpagarmepedido', 'tblpagamento', 'codpagarmepedido', 'tblpagarmepedido(codpagarmepedido)'),
        ('fk_tblpagamento_tblsauruspedido', 'tblpagamento', 'codsauruspedido', 'tblsauruspedido(codsauruspedido)'),
        ('fk_tblpagamento_tblliopedido', 'tblpagamento', 'codliopedido', 'tblliopedido(codliopedido)'),
        ('fk_tblpagamento_tbltitulo', 'tblpagamento', 'codtitulo', 'tbltitulo(codtitulo)'),
        ('fk_tblpagamento_tblusuario', 'tblpagamento', 'codusuariocriacao', 'tblusuario(codusuario)'),
        ('fk_tblpagamento_tblusuario_0', 'tblpagamento', 'codusuarioalteracao', 'tblusuario(codusuario)'),
        ('fk_tblnegocioparcela_tblnegocio', 'tblnegocioparcela', 'codnegocio', 'tblnegocio(codnegocio)'),
        ('fk_tblnegocioparcela_tbltitulo', 'tblnegocioparcela', 'codtitulo', 'tbltitulo(codtitulo)'),
        ('fk_tblnegocioparcela_tblusuario', 'tblnegocioparcela', 'codusuariocriacao', 'tblusuario(codusuario)'),
        ('fk_tblnegocioparcela_tblusuario_0', 'tblnegocioparcela', 'codusuarioalteracao', 'tblusuario(codusuario)')
    ) AS t(nome, tabela, coluna, alvo)
    LOOP
        IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = r.nome) THEN
            EXECUTE format('ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s ON UPDATE CASCADE',
                r.tabela, r.nome, r.coluna, r.alvo);
        END IF;
    END LOOP;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'tblpagamento_meio_check') THEN
        ALTER TABLE tblpagamento ADD CONSTRAINT tblpagamento_meio_check
            CHECK (meio IN (1, 2, 3, 4, 12, 15, 16, 17, 18, 91, 92, 93, 94, 99));
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'tblpagamento_estado_check') THEN
        ALTER TABLE tblpagamento ADD CONSTRAINT tblpagamento_estado_check
            CHECK (estado IN ('P', 'E', 'C'));
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'tblpagamento_motivo_check') THEN
        ALTER TABLE tblpagamento ADD CONSTRAINT tblpagamento_motivo_check
            CHECK (motivo IS NULL OR motivo IN ('T', 'F', 'R', 'A'));
    END IF;
    -- principal > 0; zero so' para compensacao (M6)
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'tblpagamento_valores_check') THEN
        ALTER TABLE tblpagamento ADD CONSTRAINT tblpagamento_valores_check
            CHECK ((principal > 0 OR (principal = 0 AND meio = 91))
                AND juros >= 0 AND multa >= 0 AND desconto >= 0
                AND total = principal + juros + multa - desconto
                AND COALESCE(valortroco, 0) >= 0);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'tblnegocioparcela_condicao_check') THEN
        ALTER TABLE tblnegocioparcela ADD CONSTRAINT tblnegocioparcela_condicao_check
            CHECK (condicao IN ('F', 'P', 'B', 'E', 'X', 'V'));
    END IF;
    -- titulo de valor zero existe no historico (13), por isso >= 0
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'tblnegocioparcela_valor_check') THEN
        ALTER TABLE tblnegocioparcela ADD CONSTRAINT tblnegocioparcela_valor_check
            CHECK (valor >= 0 AND juros >= 0 AND juros <= valor);
    END IF;
END $$;

CREATE UNIQUE INDEX IF NOT EXISTS uk_tblpagamento_uuid ON tblpagamento (uuid);
CREATE UNIQUE INDEX IF NOT EXISTS uk_tblpagamento_codpixcob ON tblpagamento (codpixcob);
CREATE UNIQUE INDEX IF NOT EXISTS uk_tblpagamento_codpagarmepedido ON tblpagamento (codpagarmepedido);
CREATE INDEX IF NOT EXISTS idx_tblpagamento_codnegocio ON tblpagamento (codnegocio);
CREATE INDEX IF NOT EXISTS idx_tblpagamento_estado_lancamento ON tblpagamento (estado, lancamento);
CREATE INDEX IF NOT EXISTS idx_tblpagamento_codportadororigem ON tblpagamento (codportadororigem);
CREATE INDEX IF NOT EXISTS idx_tblpagamento_codportadordestino ON tblpagamento (codportadordestino);
CREATE INDEX IF NOT EXISTS idx_tblpagamento_codmaquineta_lancamento ON tblpagamento (codmaquineta, lancamento);
CREATE INDEX IF NOT EXISTS idx_tblpagamento_codpagamentoorigem ON tblpagamento (codpagamentoorigem);
CREATE INDEX IF NOT EXISTS idx_tblpagamento_codtitulo ON tblpagamento (codtitulo);
CREATE INDEX IF NOT EXISTS idx_tblpagamento_codsauruspedido ON tblpagamento (codsauruspedido);
CREATE INDEX IF NOT EXISTS idx_tblpagamento_codliopedido ON tblpagamento (codliopedido);
CREATE UNIQUE INDEX IF NOT EXISTS uk_tblnegocioparcela_uuid ON tblnegocioparcela (uuid);
CREATE INDEX IF NOT EXISTS idx_tblnegocioparcela_codnegocio ON tblnegocioparcela (codnegocio);
CREATE INDEX IF NOT EXISTS idx_tblnegocioparcela_codtitulo ON tblnegocioparcela (codtitulo);
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.columns
               WHERE table_name = 'tblnegocioparcela' AND column_name = 'uuidforma') THEN
        CREATE INDEX IF NOT EXISTS idx_tblnegocioparcela_uuidforma ON tblnegocioparcela (uuidforma);
    END IF;
END $$;

ALTER TABLE tbltitulo ADD COLUMN IF NOT EXISTS codnegocioparcela bigint;
ALTER TABLE tblmovimentotitulo ADD COLUMN IF NOT EXISTS codpagamento bigint;
ALTER TABLE tblcheque ADD COLUMN IF NOT EXISTS codpagamento bigint;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_tbltitulo_tblnegocioparcela') THEN
        ALTER TABLE tbltitulo ADD CONSTRAINT fk_tbltitulo_tblnegocioparcela
            FOREIGN KEY (codnegocioparcela) REFERENCES tblnegocioparcela(codnegocioparcela) ON UPDATE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_tblmovimentotitulo_tblpagamento') THEN
        ALTER TABLE tblmovimentotitulo ADD CONSTRAINT fk_tblmovimentotitulo_tblpagamento
            FOREIGN KEY (codpagamento) REFERENCES tblpagamento(codpagamento) ON UPDATE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_tblcheque_tblpagamento') THEN
        ALTER TABLE tblcheque ADD CONSTRAINT fk_tblcheque_tblpagamento
            FOREIGN KEY (codpagamento) REFERENCES tblpagamento(codpagamento) ON UPDATE CASCADE;
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_tbltitulo_codnegocioparcela ON tbltitulo (codnegocioparcela);
CREATE INDEX IF NOT EXISTS idx_tblmovimentotitulo_codpagamento ON tblmovimentotitulo (codpagamento);
CREATE INDEX IF NOT EXISTS idx_tblcheque_codpagamento ON tblcheque (codpagamento);

COMMIT;

-- ---------------------------------------------------------------------
-- 2. Copia do historico (so' enquanto tblnegocioformapagamento for tabela)
-- ---------------------------------------------------------------------
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '60min';

DO $$
DECLARE
    v_erro bigint;
    v_antes_titulo bigint;
    v_antes_mov bigint;
    v_antes_cheque bigint;
    v_texto text;
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_class c JOIN pg_namespace n ON n.oid = c.relnamespace
        WHERE c.relname = 'tblnegocioformapagamento' AND n.nspname = current_schema() AND c.relkind = 'r'
    ) THEN
        RAISE NOTICE 'tblnegocioformapagamento ja e'' view: historico ja copiado.';
        RETURN;
    END IF;

    SELECT count(*) INTO v_antes_titulo FROM tbltitulo WHERE codnegocioformapagamento IS NOT NULL;
    SELECT count(*) INTO v_antes_mov FROM tblmovimentotitulo WHERE codnegocioformapagamento IS NOT NULL;
    SELECT count(*) INTO v_antes_cheque FROM tblcheque WHERE codnegocioformapagamento IS NOT NULL;

    -- classificacao de cada forma antiga: R = vira parcela(s), P = pagamento
    CREATE TEMP TABLE _forma ON COMMIT DROP AS
    SELECT
        nfp.*,
        n.codnegociostatus,
        n.lancamento AS negociolancamento,
        n.alteracao AS negocioalteracao,
        n.codusuarioalteracao AS negociousuarioalteracao,
        n.justificativa AS negociojustificativa,
        n.codpdv,
        n.codfilial,
        COALESCE(nfp.valortotal, nfp.valorpagamento + COALESCE(nfp.valorjuros, 0)) AS totalantigo,
        EXISTS (SELECT 1 FROM tbltitulo t WHERE t.codnegocioformapagamento = nfp.codnegocioformapagamento) AS temtitulo,
        CASE
            WHEN EXISTS (SELECT 1 FROM tbltitulo t WHERE t.codnegocioformapagamento = nfp.codnegocioformapagamento) THEN 'R'
            WHEN nfp.codformapagamento = 1030 AND nfp.tipo = 90 AND nfp.codtitulo IS NULL THEN 'R'
            WHEN fp.avista THEN 'P'
            ELSE 'R'
        END AS classe,
        NULL::numeric(14,2) AS trococorrigido
    FROM tblnegocioformapagamento nfp
    JOIN tblnegocio n ON n.codnegocio = nfp.codnegocio
    JOIN tblformapagamento fp ON fp.codformapagamento = nfp.codformapagamento;

    CREATE INDEX ON _forma (codnegocioformapagamento);
    CREATE INDEX ON _forma (codnegocio);
    ANALYZE _forma;
    RAISE NOTICE '% formas classificadas', clock_timestamp();

    -- troco lancado em dobro: redistribui o troco em dinheiro do negocio
    -- (maior pagamento primeiro) para fechar com o total da venda
    CREATE TEMP TABLE _trocoerrado ON COMMIT DROP AS
    SELECT DISTINCT codnegocio FROM _forma
    WHERE classe = 'P' AND COALESCE(valortroco, 0) > valorpagamento;

    WITH neg AS (
        SELECT f.codnegocio,
            n.valortotal
            - COALESCE(SUM(f.totalantigo - COALESCE(f.valortroco, 0)) FILTER (WHERE NOT (f.classe = 'P' AND f.codformapagamento = 1010)), 0)
            AS liquidodinheiro,
            SUM(f.valorpagamento) FILTER (WHERE f.classe = 'P' AND f.codformapagamento = 1010) AS entregue
        FROM _forma f
        JOIN tblnegocio n ON n.codnegocio = f.codnegocio
        WHERE f.codnegocio IN (SELECT codnegocio FROM _trocoerrado)
        GROUP BY f.codnegocio, n.valortotal
    ), ordem AS (
        SELECT f.codnegocioformapagamento, f.valorpagamento,
            GREATEST(neg.entregue - neg.liquidodinheiro, 0) AS trocototal,
            COALESCE(SUM(f.valorpagamento) OVER (PARTITION BY f.codnegocio ORDER BY f.valorpagamento DESC, f.codnegocioformapagamento
                ROWS BETWEEN UNBOUNDED PRECEDING AND 1 PRECEDING), 0) AS antes
        FROM _forma f
        JOIN neg ON neg.codnegocio = f.codnegocio
        WHERE f.classe = 'P' AND f.codformapagamento = 1010
    )
    UPDATE _forma f
    SET trococorrigido = LEAST(o.valorpagamento, GREATEST(o.trocototal - o.antes, 0))
    FROM ordem o
    WHERE o.codnegocioformapagamento = f.codnegocioformapagamento;

    ANALYZE _trocoerrado;

    -- ---------------- pagamentos ----------------
    INSERT INTO tblpagamento (
        codpagamento, uuid, codportadororigem, codportadordestino, meio, estado,
        principal, juros, total, valortroco, parcelas,
        lancamento, efetivacao, codusuarioefetivacao,
        cancelamento, codusuariocancelamento, justificativa,
        codpessoa, codpdv, codfilial, codnegocio,
        codmaquineta, bandeira, autorizacao,
        codpixcob, codpagarmepedido, codsauruspedido, codliopedido, codtitulo,
        cmc7, chequevencimento, chequecnpj, chequeemitente,
        criacao, codusuariocriacao, alteracao, codusuarioalteracao
    )
    SELECT
        f.codnegocioformapagamento,
        f.uuid,
        -- cartao negativo: estorno lancado na propria venda, saiu da adquirente
        CASE WHEN f.valorpagamento < 0 THEN
            (SELECT po.codportador FROM tblportador po
             WHERE po.tipo = 'A' AND po.codpessoa = f.codpessoa AND po.inativo IS NULL
             ORDER BY po.codportador LIMIT 1)
        END,
        -- Mercos Pay: destino = portador Mercos Pay (separa do cartao sem tipo)
        CASE WHEN f.codformapagamento = 5607 THEN 202046 END,
        CASE
            WHEN f.tipo IN (1, 2, 3, 4, 12, 15, 16, 17, 18, 99) THEN f.tipo
            WHEN f.codformapagamento = 1010 THEN 1
            WHEN f.codformapagamento = 1020 THEN 2
            WHEN f.codformapagamento = 1030 THEN 12
            WHEN f.codformapagamento = 5604 THEN 17
            WHEN f.codformapagamento = 5605 THEN
                CASE (SELECT pp.tipo FROM tblpagarmepedido pp WHERE pp.codpagarmepedido = f.codpagarmepedido)
                    WHEN 1 THEN 4
                    WHEN 2 THEN 3
                    WHEN 3 THEN 3
                    WHEN 4 THEN 3
                    ELSE 99
                END
            ELSE 99
        END,
        CASE f.codnegociostatus WHEN 1 THEN 'P' WHEN 3 THEN 'C' ELSE 'E' END,
        abs(f.valorpagamento) - COALESCE(f.trococorrigido, f.valortroco, 0),
        COALESCE(f.valorjuros, 0),
        abs(f.valorpagamento) - COALESCE(f.trococorrigido, f.valortroco, 0) + COALESCE(f.valorjuros, 0),
        NULLIF(COALESCE(f.trococorrigido, f.valortroco, 0), 0),
        f.parcelas,
        COALESCE(f.criacao, f.negociolancamento),
        CASE WHEN f.codnegociostatus <> 1 THEN COALESCE(f.criacao, f.negociolancamento) END,
        CASE WHEN f.codnegociostatus <> 1 THEN f.codusuariocriacao END,
        CASE WHEN f.codnegociostatus = 3 THEN COALESCE(f.negocioalteracao, f.negociolancamento) END,
        CASE WHEN f.codnegociostatus = 3 THEN f.negociousuarioalteracao END,
        CASE WHEN f.codnegociostatus = 3 THEN f.negociojustificativa END,
        f.codpessoa, f.codpdv, f.codfilial, f.codnegocio,
        f.codmaquineta, f.bandeira, f.autorizacao,
        f.codpixcob, f.codpagarmepedido, f.codsauruspedido, f.codliopedido, f.codtitulo,
        f.cmc7, f.chequevencimento, f.chequecnpj, f.chequeemitente,
        f.criacao, f.codusuariocriacao, f.alteracao, f.codusuarioalteracao
    FROM _forma f
    WHERE f.classe = 'P'
      AND abs(f.valorpagamento) - COALESCE(f.trococorrigido, f.valortroco, 0) + COALESCE(f.valorjuros, 0) > 0
      AND abs(f.valorpagamento) - COALESCE(f.trococorrigido, f.valortroco, 0) > 0;

    -- contrario aponta para o cartao da mesma autorizacao no negocio
    UPDATE tblpagamento p
    SET codpagamentoorigem = (
        SELECT o.codpagamento FROM tblpagamento o
        WHERE o.codnegocio = p.codnegocio
          AND o.codpagamento <> p.codpagamento
          AND o.codportadororigem IS NULL
          AND lower(o.autorizacao) = lower(p.autorizacao)
        ORDER BY o.codpagamento DESC LIMIT 1
    )
    WHERE p.codportadororigem IS NOT NULL
      AND p.codpagamento IN (SELECT codnegocioformapagamento FROM _forma WHERE valorpagamento < 0);

    SELECT count(*) INTO v_erro FROM _forma
    WHERE classe = 'P' AND valorpagamento < 0
      AND codnegocioformapagamento NOT IN (SELECT codpagamento FROM tblpagamento WHERE codportadororigem IS NOT NULL);
    IF v_erro > 0 THEN
        RAISE EXCEPTION '% cartoes negativos sem portador da adquirente. Carga abortada.', v_erro;
    END IF;

    ANALYZE tblpagamento;
    RAISE NOTICE '% pagamentos copiados', clock_timestamp();

    PERFORM setval('tblpagamento_codpagamento_seq',
        GREATEST((SELECT last_value FROM tblnegocioformapagamento_codnegocioformapagamento_seq),
                 (SELECT COALESCE(max(codpagamento), 1) FROM tblpagamento)));

    -- ---------------- parcelas ----------------
    -- uma por titulo (valor e vencimento do titulo)
    INSERT INTO tblnegocioparcela (
        codnegocio, condicao, numero, vencimento, valor, juros, codtitulo, uuidforma,
        criacao, codusuariocriacao, alteracao, codusuarioalteracao
    )
    SELECT
        f.codnegocio,
        CASE f.codformapagamento
            WHEN 1030 THEN 'V'
            WHEN 3010 THEN 'F' WHEN 3020 THEN 'F' WHEN 5601 THEN 'F'
            WHEN 5100 THEN 'P'
            WHEN 4100 THEN 'B'
            WHEN 1099 THEN 'E' WHEN 1010 THEN 'E'
            WHEN 5606 THEN 'X' WHEN 5602 THEN 'X'
        END,
        row_number() OVER (PARTITION BY f.codnegocioformapagamento ORDER BY t.vencimento, t.codtitulo),
        t.vencimento,
        abs(t.valor),
        0,
        t.codtitulo,
        f.uuid,
        t.criacao, t.codusuariocriacao, t.alteracao, t.codusuarioalteracao
    FROM _forma f
    JOIN tbltitulo t ON t.codnegocioformapagamento = f.codnegocioformapagamento
    WHERE f.classe = 'R';

    -- sem titulo (negocio cancelado ou aberto): uma parcela com o valor da forma
    INSERT INTO tblnegocioparcela (
        codnegocio, condicao, numero, vencimento, valor, juros, uuidforma,
        criacao, codusuariocriacao, alteracao, codusuarioalteracao
    )
    SELECT
        f.codnegocio,
        CASE f.codformapagamento
            WHEN 1030 THEN 'V'
            WHEN 3010 THEN 'F' WHEN 3020 THEN 'F' WHEN 5601 THEN 'F'
            WHEN 5100 THEN 'P'
            WHEN 4100 THEN 'B'
            WHEN 1099 THEN 'E' WHEN 1010 THEN 'E'
            WHEN 5606 THEN 'X' WHEN 5602 THEN 'X'
        END,
        1,
        CASE WHEN f.codformapagamento IN (3010, 3020, 5601)
            THEN (date_trunc('month', f.negociolancamento) + interval '2 month' - interval '1 day')::date
            ELSE f.negociolancamento::date + COALESCE(f.dias, 0)
        END,
        f.totalantigo,
        LEAST(COALESCE(f.valorjuros, 0), f.totalantigo),
        f.uuid,
        f.criacao, f.codusuariocriacao, f.alteracao, f.codusuarioalteracao
    FROM _forma f
    WHERE f.classe = 'R' AND NOT f.temtitulo AND f.totalantigo > 0;

    SELECT count(*) INTO v_erro FROM tblnegocioparcela WHERE condicao IS NULL;
    IF v_erro > 0 THEN
        RAISE EXCEPTION '% parcelas sem condicao (forma antiga sem regra). Carga abortada.', v_erro;
    END IF;

    -- juros da forma a prazo (4 no historico) ficam na ultima parcela
    UPDATE tblnegocioparcela np
    SET juros = f.valorjuros
    FROM _forma f
    WHERE f.classe = 'R' AND f.temtitulo AND COALESCE(f.valorjuros, 0) > 0
      AND np.uuidforma = f.uuid
      AND np.numero = (SELECT max(x.numero) FROM tblnegocioparcela x WHERE x.uuidforma = f.uuid)
      AND np.valor >= f.valorjuros;

    ANALYZE tblnegocioparcela;
    RAISE NOTICE '% parcelas copiadas', clock_timestamp();

    -- ---------------- reapontamentos ----------------
    UPDATE tbltitulo t
    SET codnegocioparcela = np.codnegocioparcela
    FROM tblnegocioparcela np
    WHERE np.codtitulo = t.codtitulo
      AND t.codnegocioformapagamento IS NOT NULL;

    UPDATE tblmovimentotitulo m
    SET codpagamento = m.codnegocioformapagamento
    WHERE m.codnegocioformapagamento IS NOT NULL;

    UPDATE tblcheque c
    SET codpagamento = c.codnegocioformapagamento
    WHERE c.codnegocioformapagamento IS NOT NULL;

    RAISE NOTICE '% titulos, movimentos e cheques reapontados', clock_timestamp();

    -- ---------------- conferencia ----------------
    SELECT count(*) INTO v_erro FROM tbltitulo WHERE codnegocioformapagamento IS NOT NULL AND codnegocioparcela IS NULL;
    IF v_erro > 0 OR (SELECT count(*) FROM tbltitulo WHERE codnegocioparcela IS NOT NULL) <> v_antes_titulo THEN
        RAISE EXCEPTION 'Titulos reapontados nao batem (% sem parcela). Carga abortada.', v_erro;
    END IF;

    SELECT count(*) INTO v_erro FROM tblmovimentotitulo m
    WHERE m.codnegocioformapagamento IS NOT NULL
      AND NOT EXISTS (SELECT 1 FROM tblpagamento p WHERE p.codpagamento = m.codnegocioformapagamento);
    IF v_erro > 0 OR (SELECT count(*) FROM tblmovimentotitulo WHERE codpagamento IS NOT NULL) <> v_antes_mov THEN
        RAISE EXCEPTION 'Movimentos reapontados nao batem (% sem pagamento). Carga abortada.', v_erro;
    END IF;

    SELECT count(*) INTO v_erro FROM tblcheque c
    WHERE c.codnegocioformapagamento IS NOT NULL
      AND NOT EXISTS (SELECT 1 FROM tblpagamento p WHERE p.codpagamento = c.codnegocioformapagamento);
    IF v_erro > 0 OR (SELECT count(*) FROM tblcheque WHERE codpagamento IS NOT NULL) <> v_antes_cheque THEN
        RAISE EXCEPTION 'Cheques reapontados nao batem (% sem pagamento). Carga abortada.', v_erro;
    END IF;

    -- Σ por negocio: antes = sum(total - troco) das formas (a prazo com
    -- titulo: soma dos titulos); depois = Σ total dos pagamentos (contrario
    -- subtrai) + Σ parcelas. Diferenca so' e' aceita nos negocios de troco
    -- em dobro, que tem de fechar com o total da venda.
    CREATE TEMP TABLE _conf ON COMMIT DROP AS
    WITH antes AS (
        SELECT f.codnegocio, sum(
            CASE
                WHEN f.classe = 'R' AND f.temtitulo THEN
                    (SELECT sum(abs(t.valor)) FROM tbltitulo t WHERE t.codnegocioformapagamento = f.codnegocioformapagamento)
                WHEN f.classe = 'R' THEN GREATEST(f.totalantigo, 0)
                ELSE f.totalantigo - COALESCE(f.valortroco, 0)
            END) AS valor
        FROM _forma f GROUP BY f.codnegocio
    ), depois AS (
        SELECT codnegocio, sum(v) AS valor FROM (
            SELECT codnegocio, CASE WHEN codportadororigem IS NOT NULL THEN -total ELSE total END AS v
            FROM tblpagamento WHERE codnegocio IS NOT NULL
            UNION ALL
            SELECT codnegocio, valor FROM tblnegocioparcela
        ) x GROUP BY codnegocio
    )
    SELECT a.codnegocio, a.valor AS antes, COALESCE(d.valor, 0) AS depois
    FROM antes a LEFT JOIN depois d ON d.codnegocio = a.codnegocio
    WHERE a.valor <> COALESCE(d.valor, 0);

    ANALYZE _conf;
    RAISE NOTICE '% conferencia por negocio calculada (% diferencas)', clock_timestamp(), (SELECT count(*) FROM _conf);

    -- forma antiga incoerente (valortotal <> valorpagamento + juros): o
    -- pagamento sai do valorpagamento; a diferenca e' listada, nao aborta
    CREATE TEMP TABLE _incoerente ON COMMIT DROP AS
    SELECT DISTINCT codnegocio FROM _forma
    WHERE classe = 'P' AND valortotal IS NOT NULL
      AND valortotal <> valorpagamento + COALESCE(valorjuros, 0);
    ANALYZE _incoerente;

    SELECT count(*) INTO v_erro FROM _conf
    WHERE codnegocio NOT IN (SELECT codnegocio FROM _trocoerrado)
      AND codnegocio NOT IN (SELECT codnegocio FROM _incoerente);
    IF v_erro > 0 THEN
        SELECT string_agg(codnegocio || ': ' || antes || ' -> ' || depois, ', ') INTO v_texto
        FROM (SELECT * FROM _conf
              WHERE codnegocio NOT IN (SELECT codnegocio FROM _trocoerrado)
                AND codnegocio NOT IN (SELECT codnegocio FROM _incoerente) LIMIT 10) x;
        RAISE EXCEPTION '% negocios com total diferente (%). Carga abortada.', v_erro, v_texto;
    END IF;

    SELECT string_agg(codnegocio || ': ' || antes || ' -> ' || depois, ', ' ORDER BY codnegocio) INTO v_texto
    FROM _conf WHERE codnegocio IN (SELECT codnegocio FROM _incoerente)
      AND codnegocio NOT IN (SELECT codnegocio FROM _trocoerrado);
    RAISE NOTICE 'Forma antiga incoerente (valortotal <> valorpagamento + juros), total pelo valorpagamento: %', COALESCE(v_texto, 'nenhum');

    SELECT count(*) INTO v_erro
    FROM _trocoerrado te
    JOIN tblnegocio n ON n.codnegocio = te.codnegocio
    WHERE n.valortotal <> (
        COALESCE((SELECT sum(CASE WHEN codportadororigem IS NOT NULL THEN -total ELSE total END) FROM tblpagamento p WHERE p.codnegocio = te.codnegocio), 0)
        + COALESCE((SELECT sum(valor) FROM tblnegocioparcela np WHERE np.codnegocio = te.codnegocio), 0));
    RAISE NOTICE 'Troco em dobro corrigido em % negocios (% nao fecham com o total da venda: dado antigo inconsistente).',
        (SELECT count(*) FROM _trocoerrado), v_erro;

    RAISE NOTICE 'Pagamentos: %; parcelas: %; formas nao copiadas (valor zero): %; contrarios: %.',
        (SELECT count(*) FROM tblpagamento WHERE codnegocio IS NOT NULL),
        (SELECT count(*) FROM tblnegocioparcela),
        (SELECT count(*) FROM _forma f WHERE f.classe = 'P' AND NOT EXISTS (SELECT 1 FROM tblpagamento p WHERE p.codpagamento = f.codnegocioformapagamento))
          + (SELECT count(*) FROM _forma f WHERE f.classe = 'R' AND NOT f.temtitulo AND f.totalantigo <= 0),
        (SELECT count(*) FROM tblpagamento WHERE codportadororigem IS NOT NULL AND codnegocio IS NOT NULL);
    RAISE NOTICE 'Reapontados: % titulos, % movimentos, % cheques.', v_antes_titulo, v_antes_mov, v_antes_cheque;

    RAISE NOTICE '% conferencia ok', clock_timestamp();

    -- ---------------- tabela antiga sai ----------------
    ALTER TABLE tbltitulo DROP COLUMN codnegocioformapagamento;
    ALTER TABLE tblmovimentotitulo DROP COLUMN codnegocioformapagamento;
    ALTER TABLE tblcheque DROP COLUMN codnegocioformapagamento;
    ALTER TABLE tblportadormovimento DROP COLUMN IF EXISTS codnegocioformapagamento;
    -- vwnegocio e vwnegocio_listagem (SQLs avulsos do MGdb) usam a de
    -- totais: ela fica, redefinida sobre pagamentos e parcelas, com as
    -- mesmas colunas (valorpagamento antigo = principal + troco)
    CREATE OR REPLACE VIEW vwnegocioformapagamentototais AS
    SELECT x.codnegocio,
        sum(x.avista) AS valorpagamentoavista,
        sum(x.aprazo) AS valorpagamentoaprazo,
        sum(x.avista + x.aprazo) AS valorpagamentototal
    FROM (
        SELECT p.codnegocio,
            ((CASE WHEN p.codportadororigem IS NOT NULL THEN -1 ELSE 1 END) * (p.principal + COALESCE(p.valortroco, 0)))::numeric AS avista,
            0::numeric AS aprazo
        FROM tblpagamento p
        WHERE p.codnegocio IS NOT NULL
        UNION ALL
        SELECT np.codnegocio, 0::numeric, (np.valor - np.juros)::numeric
        FROM tblnegocioparcela np
    ) x
    GROUP BY x.codnegocio;
    DROP VIEW IF EXISTS vwnegocioformapagamento;
    DROP FUNCTION IF EXISTS fntblnegocio_atualiza_valoraprazo();
    DROP TABLE tblnegocioformapagamento;
END $$;

-- ---------------------------------------------------------------------
-- 3. View com o nome antigo (Totais de Caixa do MG Lara), ate sair.
--    Pagamento: codigo = codpagamento. Parcelas: uma linha por condicao,
--    codigo negativo (nunca colide com pagamento).
--    Forma deduzida de meio/condicao/integracao (Fechamento sai 3020;
--    Stone historico sem pedido sai 2010).
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW tblnegocioformapagamento AS
SELECT
    p.codpagamento AS codnegocioformapagamento,
    p.codnegocio,
    (CASE
        WHEN p.codpixcob IS NOT NULL THEN 5604
        WHEN p.codpagarmepedido IS NOT NULL THEN 5605
        WHEN p.codsauruspedido IS NOT NULL THEN 5608
        WHEN p.codliopedido IS NOT NULL THEN 5603
        WHEN p.meio = 1 THEN 1010
        WHEN p.meio = 2 THEN 1020
        WHEN p.meio = 12 THEN 1030
        WHEN p.meio = 99 AND p.codmaquineta IS NULL AND po.codportador = 202046 THEN 5607
        ELSE 2010
    END)::bigint AS codformapagamento,
    ((CASE WHEN p.codportadororigem IS NOT NULL THEN -1 ELSE 1 END) * (p.principal + COALESCE(p.valortroco, 0)))::numeric(14,2) AS valorpagamento,
    p.alteracao,
    p.codusuarioalteracao,
    p.criacao,
    p.codusuariocriacao,
    p.codliopedido,
    p.codpixcob,
    p.codpagarmepedido,
    NULLIF(p.juros, 0)::numeric(14,2) AS valorjuros,
    p.valortroco,
    true AS avista,
    p.meio AS tipo,
    p.autorizacao::varchar(40) AS autorizacao,
    p.bandeira,
    (p.codpixcob IS NOT NULL OR p.codpagarmepedido IS NOT NULL OR p.codsauruspedido IS NOT NULL OR p.codliopedido IS NOT NULL) AS integracao,
    p.codpessoa,
    p.uuid,
    ((CASE WHEN p.codportadororigem IS NOT NULL THEN -1 ELSE 1 END) * (p.total + COALESCE(p.valortroco, 0)))::numeric(14,2) AS valortotal,
    p.parcelas,
    NULL::numeric(14,2) AS valorparcela,
    p.codtitulo,
    p.codsauruspedido,
    NULL::smallint AS dias,
    m.serial::varchar(50) AS serialmaquineta,
    p.cmc7,
    p.chequevencimento,
    p.chequecnpj,
    p.chequeemitente,
    p.codmaquineta
FROM tblpagamento p
LEFT JOIN tblmaquineta m ON m.codmaquineta = p.codmaquineta
LEFT JOIN tblportador po ON po.codportador = p.codportadordestino
WHERE p.codnegocio IS NOT NULL
UNION ALL
SELECT
    -min(np.codnegocioparcela) AS codnegocioformapagamento,
    np.codnegocio,
    (CASE np.condicao
        WHEN 'F' THEN 3020
        WHEN 'P' THEN 5100
        WHEN 'B' THEN 4100
        WHEN 'E' THEN 1099
        WHEN 'X' THEN 5606
        WHEN 'V' THEN 1030
    END)::bigint AS codformapagamento,
    (sum(np.valor) - sum(np.juros))::numeric(14,2) AS valorpagamento,
    max(np.alteracao) AS alteracao,
    min(np.codusuarioalteracao) AS codusuarioalteracao,
    min(np.criacao) AS criacao,
    min(np.codusuariocriacao) AS codusuariocriacao,
    NULL::bigint AS codliopedido,
    NULL::bigint AS codpixcob,
    NULL::bigint AS codpagarmepedido,
    NULLIF(sum(np.juros), 0)::numeric(14,2) AS valorjuros,
    NULL::numeric(14,2) AS valortroco,
    false AS avista,
    (CASE np.condicao WHEN 'B' THEN 15 WHEN 'X' THEN 16 WHEN 'V' THEN 90 ELSE 5 END)::smallint AS tipo,
    NULL::varchar(40) AS autorizacao,
    NULL::smallint AS bandeira,
    false AS integracao,
    NULL::bigint AS codpessoa,
    min(np.uuid::text)::uuid AS uuid,
    sum(np.valor)::numeric(14,2) AS valortotal,
    count(*)::smallint AS parcelas,
    min(np.valor)::numeric(14,2) AS valorparcela,
    NULL::bigint AS codtitulo,
    NULL::bigint AS codsauruspedido,
    NULL::smallint AS dias,
    NULL::varchar(50) AS serialmaquineta,
    NULL::varchar(50) AS cmc7,
    NULL::date AS chequevencimento,
    NULL::numeric(14,0) AS chequecnpj,
    NULL::varchar(100) AS chequeemitente,
    NULL::bigint AS codmaquineta
FROM tblnegocioparcela np
GROUP BY np.codnegocio, np.condicao;

COMMIT;

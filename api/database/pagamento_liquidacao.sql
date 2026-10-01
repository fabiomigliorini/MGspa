-- =====================================================================
-- Pagamento no lugar da liquidacao (M6 do plano doc-3, fechamento de caixa).
--
-- A liquidacao de titulos some: cada liquidacao vira um pagamento
-- (tblpagamento, codigo novo; o antigo fica em codliquidacaotituloantigo) e o
-- movimento de titulo passa a apontar para o pagamento (codpagamento, que ja'
-- existe desde o M4 para o vale usado no PDV). tblmovimentotitulo perde
-- codliquidacaotitulo, a tabela cai e no lugar fica a VIEW
-- tblliquidacaotitulo (pagamentos que movimentam titulo, colunas antigas,
-- debito/credito calculados) para o Totais de Caixa do MG Lara, ate sair.
--
-- Copia de cada liquidacao:
--   valores    soma das linhas de baixa (sem os estornos): total = |soma do
--              total| (o dinheiro que andou); juros, multa e desconto = soma
--              das colunas; principal = total - juros - multa + desconto.
--              Encontro de contas misto que daria principal negativo fica com
--              principal = total e juros/multa/desconto zerados no pagamento
--              (as linhas do movimento continuam com eles).
--   sentido    soma do total < 0 = entrou dinheiro: destino = portador;
--              > 0 = saiu: origem = portador; = 0 = encontro de contas sem
--              dinheiro: meio compensacao, sem portador.
--   meio       pelo portador antigo: especie -> dinheiro; banco e adquirente
--              -> transferencia; cartao da empresa -> credito; Acerto Folha
--              -> folha; Barter -> permuta; Perda por Prazo -> perda;
--              Programacao Pagamentos e Cred Pis/Cofins -> compensacao;
--              demais "outros" (Carteira, Cobrador Externo...) -> outros.
--   estado     E; C se estornada (cancelamento = estorno).
--   lancamento = transacao; efetivacao = criacao.
--
-- Baixas sem liquidacao tambem ganham pagamento (secao 5): cada linha de
-- baixa de boleto (BB pela API ou retorno Bradesco) vira um pagamento meio
-- boleto (destino/origem = portador do boleto pelo sinal; estornada = C), e
-- cada evento de acerto de RH vira um pagamento pela forma (B Recarga Bee e
-- saldo zero = compensacao, D dinheiro, F folha; inativo = C; sem portador no
-- historico). Os movimentos passam a apontar para eles.
--
-- Colunas novas em tblpagamento: codliquidacaotituloantigo (so' historico) e
-- codperiodocolaboradoracerto (acerto de RH, um pagamento por evento).
-- Pseudoportadores (Acerto Folha, Barter, Perda por Prazo, Programacao
-- Pagamentos, Cred Pis/Cofins) sao inativados: viraram meio do pagamento.
--
-- Conferencia dentro do script (aborta se nao fechar): numero de
-- liquidacoes = pagamentos copiados; movimentos reapontados = movimentos que
-- tinham liquidacao; dinheiro por portador e mes antes = depois.
--
-- Roda depois do pagamento.sql (secao 1, M4). Idempotente: a copia so' roda
-- enquanto tblliquidacaotitulo for tabela. Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '30min';

-- ---------------------------------------------------------------------
-- 1. Colunas novas
-- ---------------------------------------------------------------------
ALTER TABLE tblpagamento ADD COLUMN IF NOT EXISTS codliquidacaotituloantigo bigint;
ALTER TABLE tblpagamento ADD COLUMN IF NOT EXISTS codperiodocolaboradoracerto bigint;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_tblpagamento_tblperiodocolaboradoracerto') THEN
        ALTER TABLE tblpagamento
            ADD CONSTRAINT fk_tblpagamento_tblperiodocolaboradoracerto
            FOREIGN KEY (codperiodocolaboradoracerto)
            REFERENCES tblperiodocolaboradoracerto (codperiodocolaboradoracerto) ON UPDATE CASCADE;
    END IF;
END $$;

CREATE UNIQUE INDEX IF NOT EXISTS uk_tblpagamento_codliquidacaotituloantigo
    ON tblpagamento (codliquidacaotituloantigo);
CREATE INDEX IF NOT EXISTS idx_tblpagamento_codperiodocolaboradoracerto
    ON tblpagamento (codperiodocolaboradoracerto);
CREATE INDEX IF NOT EXISTS idx_tblmovimentotitulo_codpagamento
    ON tblmovimentotitulo (codpagamento);

-- ---------------------------------------------------------------------
-- 2. Copia das liquidacoes (so' enquanto tblliquidacaotitulo for tabela)
-- ---------------------------------------------------------------------
DO $$
DECLARE
    v_liquidacoes bigint;
    v_copiados bigint;
    v_mov_antes bigint;
    v_mov_depois bigint;
    v_conflito bigint;
    v_diferencas bigint;
    v_zerados bigint;
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_class c
        JOIN pg_namespace n ON n.oid = c.relnamespace
        WHERE c.relname = 'tblliquidacaotitulo' AND c.relkind = 'r' AND n.nspname = current_schema()
    ) THEN
        RAISE NOTICE 'tblliquidacaotitulo ja e view: copia ja feita, nada a fazer';
        RETURN;
    END IF;

    -- movimento com liquidacao e pagamento ao mesmo tempo nao pode existir
    SELECT count(*) INTO v_conflito FROM tblmovimentotitulo
    WHERE codliquidacaotitulo IS NOT NULL AND codpagamento IS NOT NULL;
    IF v_conflito > 0 THEN
        RAISE EXCEPTION 'M6: % movimentos com liquidacao e pagamento ao mesmo tempo', v_conflito;
    END IF;

    -- antes: dinheiro por portador e mes das liquidacoes ativas
    CREATE TEMP TABLE m6_antes ON COMMIT DROP AS
    SELECT l.codportador, date_trunc('month', l.transacao)::date AS mes, sum(-mt.total) AS valor
    FROM tblliquidacaotitulo l
    JOIN tblmovimentotitulo mt ON mt.codliquidacaotitulo = l.codliquidacaotitulo
    WHERE l.estornado IS NULL
      AND mt.codmovimentotituloestorno IS NULL
      AND mt.codtipomovimentotitulo < 900
    GROUP BY 1, 2;

    SELECT count(*) INTO v_liquidacoes FROM tblliquidacaotitulo;
    SELECT count(*) INTO v_mov_antes FROM tblmovimentotitulo WHERE codliquidacaotitulo IS NOT NULL;

    -- soma das linhas de baixa (sem estorno) de cada liquidacao
    CREATE TEMP TABLE m6_liq ON COMMIT DROP AS
    SELECT
        l.*,
        po.tipo AS tipoportador,
        coalesce(po.codfilial, a.codfilial) AS codfilialpagamento,
        coalesce(a.t, 0) AS t,
        coalesce(a.j, 0) AS j,
        coalesce(a.mu, 0) AS mu,
        coalesce(a.d, 0) AS d
    FROM tblliquidacaotitulo l
    LEFT JOIN tblportador po ON po.codportador = l.codportador
    LEFT JOIN (
        SELECT mt.codliquidacaotitulo AS cod,
               sum(mt.total) AS t, sum(mt.juros) AS j, sum(mt.multa) AS mu, sum(mt.desconto) AS d,
               min(ti.codfilial) AS codfilial
        FROM tblmovimentotitulo mt
        JOIN tbltitulo ti ON ti.codtitulo = mt.codtitulo
        WHERE mt.codliquidacaotitulo IS NOT NULL
          AND mt.codmovimentotituloestorno IS NULL
          AND mt.codtipomovimentotitulo < 900
        GROUP BY 1
    ) a ON a.cod = l.codliquidacaotitulo;

    CREATE TEMP TABLE m6_pag ON COMMIT DROP AS
    SELECT
        x.*,
        (x.principalbruto > 0 OR (x.principalbruto = 0 AND x.meio = 91)) AS colunasok
    FROM (
        SELECT
            l.*,
            abs(l.t) AS totalabs,
            abs(l.t) - l.j - l.mu + l.d AS principalbruto,
            (CASE
                WHEN l.t = 0 THEN 91
                WHEN l.codportador = 202018 THEN 92
                WHEN l.codportador = 202007 THEN 93
                WHEN l.codportador = 202035 THEN 94
                WHEN l.codportador IN (202016, 202053) THEN 91
                WHEN l.tipoportador = 'E' THEN 1
                WHEN l.tipoportador IN ('B', 'A') THEN 18
                WHEN l.tipoportador = 'C' THEN 3
                ELSE 99
            END)::smallint AS meio
        FROM m6_liq l
    ) x;

    SELECT count(*) INTO v_zerados FROM m6_pag WHERE NOT colunasok;

    INSERT INTO tblpagamento (
        codpagamento, codportadororigem, codportadordestino, meio, estado,
        principal, juros, multa, desconto, total,
        lancamento, efetivacao, codusuarioefetivacao,
        cancelamento, codusuariocancelamento, justificativa,
        codpessoa, codfilial, observacoes, codliquidacaotituloantigo,
        criacao, codusuariocriacao, alteracao, codusuarioalteracao
    )
    SELECT
        nextval('tblpagamento_codpagamento_seq'),
        CASE WHEN p.t > 0 THEN p.codportador END,
        CASE WHEN p.t < 0 THEN p.codportador END,
        p.meio,
        CASE WHEN p.estornado IS NULL THEN 'E' ELSE 'C' END,
        CASE WHEN p.colunasok THEN p.principalbruto ELSE p.totalabs END,
        CASE WHEN p.colunasok THEN p.j ELSE 0 END,
        CASE WHEN p.colunasok THEN p.mu ELSE 0 END,
        CASE WHEN p.colunasok THEN p.d ELSE 0 END,
        p.totalabs,
        p.transacao::timestamp,
        coalesce(p.criacao, p.transacao::timestamp),
        coalesce(p.codusuario, p.codusuariocriacao),
        p.estornado,
        CASE WHEN p.estornado IS NOT NULL THEN p.codusuarioestorno END,
        CASE WHEN p.estornado IS NOT NULL THEN 'Liquidação estornada (histórico)' END,
        p.codpessoa,
        p.codfilialpagamento,
        p.observacao,
        p.codliquidacaotitulo,
        coalesce(p.criacao, p.transacao::timestamp),
        p.codusuariocriacao,
        coalesce(p.alteracao, p.criacao, p.transacao::timestamp),
        p.codusuarioalteracao
    FROM m6_pag p
    ORDER BY p.codliquidacaotitulo;

    SELECT count(*) INTO v_copiados FROM tblpagamento WHERE codliquidacaotituloantigo IS NOT NULL;
    IF v_copiados <> v_liquidacoes THEN
        RAISE EXCEPTION 'M6: % liquidacoes, % pagamentos copiados', v_liquidacoes, v_copiados;
    END IF;

    -- movimentos apontam para o pagamento
    UPDATE tblmovimentotitulo mt
       SET codpagamento = p.codpagamento
      FROM tblpagamento p
     WHERE p.codliquidacaotituloantigo = mt.codliquidacaotitulo;
    GET DIAGNOSTICS v_mov_depois = ROW_COUNT;
    IF v_mov_depois <> v_mov_antes THEN
        RAISE EXCEPTION 'M6: % movimentos com liquidacao, % reapontados', v_mov_antes, v_mov_depois;
    END IF;

    -- depois: dinheiro por portador e mes dos pagamentos copiados efetivados
    SELECT count(*) INTO v_diferencas
    FROM m6_antes a
    FULL JOIN (
        SELECT coalesce(codportadordestino, codportadororigem) AS codportador,
               date_trunc('month', lancamento)::date AS mes,
               sum(CASE WHEN codportadordestino IS NOT NULL THEN total ELSE -total END) AS valor
        FROM tblpagamento
        WHERE codliquidacaotituloantigo IS NOT NULL AND estado = 'E'
        GROUP BY 1, 2
    ) d ON d.codportador IS NOT DISTINCT FROM a.codportador AND d.mes = a.mes
    WHERE abs(coalesce(a.valor, 0) - coalesce(d.valor, 0)) > 0.005;
    IF v_diferencas > 0 THEN
        RAISE EXCEPTION 'M6: % portador/mes com dinheiro diferente antes e depois', v_diferencas;
    END IF;

    RAISE NOTICE 'M6: % liquidacoes copiadas, % movimentos reapontados, % com juros/multa/desconto zerados no pagamento (encontro misto), dinheiro por portador e mes igual',
        v_copiados, v_mov_depois, v_zerados;

    -- movimento perde a liquidacao; a tabela cai
    ALTER TABLE tblmovimentotitulo DROP CONSTRAINT IF EXISTS tblmovimentotitulo_codliquidacaotitulo_fkey;
    ALTER TABLE tblmovimentotitulo DROP COLUMN codliquidacaotitulo;
    DROP TABLE tblliquidacaotitulo;
END $$;

-- ---------------------------------------------------------------------
-- 3. View com o nome antigo para o Totais de Caixa do MG Lara
--    (pagamentos que movimentam titulo, fora a venda, a baixa de boleto pelo
--    banco e o acerto de RH, que nunca foram liquidacao; debito = pago,
--    credito = recebido, como a coluna antiga)
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW tblliquidacaotitulo AS
SELECT
    coalesce(p.codliquidacaotituloantigo, p.codpagamento) AS codliquidacaotitulo,
    p.lancamento::date AS transacao,
    coalesce(p.codportadordestino, p.codportadororigem) AS codportador,
    p.observacoes::varchar(200) AS observacao,
    p.codusuarioefetivacao AS codusuario,
    p.cancelamento AS estornado,
    p.codusuariocancelamento AS codusuarioestorno,
    p.alteracao,
    p.codusuarioalteracao,
    p.criacao,
    p.codusuariocriacao,
    m.debito,
    m.credito,
    p.codpessoa,
    p.codpdv,
    (CASE WHEN p.codportadororigem IS NOT NULL AND p.codportadordestino IS NULL THEN p.total ELSE -p.total END)::numeric(14,2) AS valor,
    p.codpagamento
FROM tblpagamento p
JOIN (
    SELECT codpagamento,
           sum(greatest(total, 0))::numeric(14,2) AS debito,
           sum(greatest(-total, 0))::numeric(14,2) AS credito
    FROM tblmovimentotitulo
    WHERE codpagamento IS NOT NULL
      AND codmovimentotituloestorno IS NULL
      AND codtipomovimentotitulo < 900
    GROUP BY codpagamento
) m ON m.codpagamento = p.codpagamento
WHERE p.codnegocio IS NULL
  AND p.codperiodocolaboradoracerto IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM tblmovimentotitulo b
      WHERE b.codpagamento = p.codpagamento
        AND (b.codtituloboleto IS NOT NULL OR b.codboletoretorno IS NOT NULL)
  );

-- ---------------------------------------------------------------------
-- 5. Baixas sem liquidacao: boleto e acerto de RH (so' as sem pagamento)
-- ---------------------------------------------------------------------
DO $$
DECLARE
    v_boletos bigint;
    v_acertos bigint;
    v_movs bigint;
BEGIN
    CREATE TEMP TABLE m6_boleto ON COMMIT DROP AS
    SELECT
        mt.codmovimentotitulo, mt.codportador, mt.transacao, mt.criacao, mt.codusuariocriacao,
        abs(mt.total) AS total,
        (abs(abs(mt.total) - (abs(mt.principal) + mt.juros + mt.multa - mt.desconto)) < 0.005
            AND abs(mt.principal) > 0) AS colunasok,
        abs(mt.principal) AS principal, mt.juros, mt.multa, mt.desconto,
        (mt.total < 0) AS entrada,
        t.codpessoa, t.codfilial,
        (SELECT min(e.criacao) FROM tblmovimentotitulo e
          WHERE e.codmovimentotituloestorno = mt.codmovimentotitulo) AS estorno,
        nextval('tblpagamento_codpagamento_seq') AS codpagamento
    FROM tblmovimentotitulo mt
    JOIN tbltitulo t ON t.codtitulo = mt.codtitulo
    WHERE (mt.codtituloboleto IS NOT NULL OR mt.codboletoretorno IS NOT NULL)
      AND mt.codpagamento IS NULL
      AND mt.codmovimentotituloestorno IS NULL
      AND mt.codtipomovimentotitulo IN (600, 930)
      AND mt.total <> 0;

    INSERT INTO tblpagamento (
        codpagamento, codportadororigem, codportadordestino, meio, estado,
        principal, juros, multa, desconto, total,
        lancamento, efetivacao, cancelamento, justificativa,
        codpessoa, codfilial, criacao, codusuariocriacao, alteracao
    )
    SELECT
        b.codpagamento,
        CASE WHEN NOT b.entrada THEN b.codportador END,
        CASE WHEN b.entrada THEN b.codportador END,
        15,
        CASE WHEN b.estorno IS NULL THEN 'E' ELSE 'C' END,
        CASE WHEN b.colunasok THEN b.principal ELSE b.total END,
        CASE WHEN b.colunasok THEN b.juros ELSE 0 END,
        CASE WHEN b.colunasok THEN b.multa ELSE 0 END,
        CASE WHEN b.colunasok THEN b.desconto ELSE 0 END,
        b.total,
        b.transacao::timestamp,
        coalesce(b.criacao, b.transacao::timestamp),
        b.estorno,
        CASE WHEN b.estorno IS NOT NULL THEN 'Baixa de boleto estornada (histórico)' END,
        b.codpessoa, b.codfilial,
        coalesce(b.criacao, b.transacao::timestamp), b.codusuariocriacao,
        coalesce(b.criacao, b.transacao::timestamp)
    FROM m6_boleto b;
    GET DIAGNOSTICS v_boletos = ROW_COUNT;

    -- a baixa e o estorno dela apontam para o pagamento
    UPDATE tblmovimentotitulo mt
       SET codpagamento = b.codpagamento
      FROM m6_boleto b
     WHERE mt.codmovimentotitulo = b.codmovimentotitulo
        OR mt.codmovimentotituloestorno = b.codmovimentotitulo;
    GET DIAGNOSTICS v_movs = ROW_COUNT;

    CREATE TEMP TABLE m6_acerto ON COMMIT DROP AS
    SELECT
        a.*, c.codpessoa, c.codfilial,
        nextval('tblpagamento_codpagamento_seq') AS codpagamento
    FROM tblperiodocolaboradoracerto a
    JOIN tblperiodocolaborador pc ON pc.codperiodocolaborador = a.codperiodocolaborador
    JOIN tblcolaborador c ON c.codcolaborador = pc.codcolaborador
    WHERE NOT EXISTS (
        SELECT 1 FROM tblpagamento p
        WHERE p.codperiodocolaboradoracerto = a.codperiodocolaboradoracerto
    );

    INSERT INTO tblpagamento (
        codpagamento, meio, estado, principal, juros, multa, desconto, total,
        lancamento, efetivacao, cancelamento, justificativa,
        codpessoa, codfilial, codperiodocolaboradoracerto, observacoes,
        criacao, codusuariocriacao, alteracao, codusuarioalteracao
    )
    SELECT
        a.codpagamento,
        (CASE
            WHEN abs(a.saldo) = 0 OR a.forma = 'B' THEN 91
            WHEN a.forma = 'D' THEN 1
            ELSE 92
        END)::smallint,
        CASE WHEN a.inativo IS NULL THEN 'E' ELSE 'C' END,
        abs(a.saldo), 0, 0, 0, abs(a.saldo),
        a.data::timestamp,
        a.criacao,
        a.inativo,
        CASE WHEN a.inativo IS NOT NULL THEN 'Acerto de RH inativado (histórico)' END,
        a.codpessoa, a.codfilial, a.codperiodocolaboradoracerto,
        left(a.observacao, 300),
        a.criacao, a.codusuariocriacao, a.alteracao, a.codusuarioalteracao
    FROM m6_acerto a;
    GET DIAGNOSTICS v_acertos = ROW_COUNT;

    UPDATE tblmovimentotitulo mt
       SET codpagamento = a.codpagamento
      FROM m6_acerto a
     WHERE mt.codperiodocolaboradoracerto = a.codperiodocolaboradoracerto
       AND mt.codpagamento IS NULL;

    RAISE NOTICE 'M6: % baixas de boleto viraram pagamento (% movimentos), % eventos de acerto viraram pagamento',
        v_boletos, v_movs, v_acertos;
END $$;

-- ---------------------------------------------------------------------
-- 4. Pseudoportadores viram meio do pagamento
-- ---------------------------------------------------------------------
UPDATE tblportador
   SET inativo = now()
 WHERE codportador IN (202018, 202007, 202035, 202016, 202053)
   AND inativo IS NULL;

-- Conferencia
SELECT meio, estado, count(*), sum(total)
FROM tblpagamento
WHERE codliquidacaotituloantigo IS NOT NULL
GROUP BY 1, 2
ORDER BY 1, 2;

SELECT 'boleto' AS origem, estado, count(*), sum(total)
FROM tblpagamento p
WHERE p.meio = 15 AND p.codnegocio IS NULL AND p.codliquidacaotituloantigo IS NULL
GROUP BY 1, 2
UNION ALL
SELECT 'acerto', estado, count(*), sum(total)
FROM tblpagamento
WHERE codperiodocolaboradoracerto IS NOT NULL
GROUP BY 1, 2;

COMMIT;

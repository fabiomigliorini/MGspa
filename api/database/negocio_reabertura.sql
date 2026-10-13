-- =====================================================================
-- Reabertura do negocio fechado (TASK-30).
--
-- SOMENTE ESTRUTURA (DDL) e views. Nenhum dado historico e' tocado.
--
--   tblnegocio.reabertura    — ultima reabertura. Com codnegociostatus = 1
--                              e' a venda reaberta (sem status novo); depois
--                              do F3 continua preenchida ("ja' foi reaberta").
--                              O fato (quem/quando) fica na tblauditoria.
--   tblnegocioparcela.inativo — parcela que ja' virou titulo e saiu da venda
--                              reaberta: nao se apaga (o titulo aponta para
--                              ela); o F3 estorna o titulo. Mesmo padrao do
--                              item e do vale do negocio.
--   tblnegocioformapagamento e vwnegocioformapagamentototais (views legadas,
--                              MG Lara e MGsis) — passam a ignorar o
--                              pagamento cancelado e a parcela inativa.
--
-- Idempotente (IF NOT EXISTS, CREATE OR REPLACE). Transacional.
-- Roda depois do pagamento.sql (as duas views nascem la') e antes do
-- tipo_titulo_limpeza.sql (o ultimo do go-live).
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

-- ADD COLUMN nulo, sem DEFAULT: so' metadado, nao reescreve a tabela, mas
-- pega ACCESS EXCLUSIVE por milissegundos; com transacao ociosa segurando
-- lock o script desiste em 5s e faz rollback. Nunca remover estes dois SET.
SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

-- ---------------------------------------------------------------------
-- 1. Colunas
-- ---------------------------------------------------------------------
ALTER TABLE tblnegocio ADD COLUMN IF NOT EXISTS reabertura timestamp(0) without time zone;
COMMENT ON COLUMN tblnegocio.reabertura IS
    'Ultima reabertura (TASK-30). Com codnegociostatus = 1: venda reaberta; o F3 mantem lancamento, codusuario e codpdv. O fato fica na tblauditoria.';

ALTER TABLE tblnegocioparcela ADD COLUMN IF NOT EXISTS inativo timestamp(0) without time zone;
COMMENT ON COLUMN tblnegocioparcela.inativo IS
    'Parcela que saiu da venda reaberta (TASK-30); o F3 estorna o titulo dela.';

-- ---------------------------------------------------------------------
-- 2. Views legadas: sem pagamento cancelado e sem parcela inativa
-- ---------------------------------------------------------------------
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
    AND p.estado <> 'C'
    UNION ALL
    SELECT np.codnegocio, 0::numeric, (np.valor - np.juros)::numeric
    FROM tblnegocioparcela np
    WHERE np.inativo IS NULL
) x
GROUP BY x.codnegocio;

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
AND p.estado <> 'C'
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
WHERE np.inativo IS NULL
GROUP BY np.codnegocio, np.condicao;

COMMIT;

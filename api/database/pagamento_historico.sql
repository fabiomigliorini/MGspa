-- =====================================================================
-- Correcao do historico das vendas (TASK-218).
--
-- Venda fechada de antes do go-live do caixa (02/10/2026) em que
-- pagamentos + parcelas nao somam o total, por tres padroes do historico:
--
-- 1. Troco em dinheiro nao gravado: o pagamento guardava o valor entregue
--    (R$ 200 numa venda de R$ 109). Passa ao formato de hoje: total = o que
--    ficou, valortroco = o resto (total + troco nao muda; a DIMP soma os
--    dois). So' quando o troco e' menor que o maior pagamento em dinheiro
--    da venda: troco do tamanho do pagamento inteiro nao e' troco.
-- 2. Pagamento repetido: a copia (mesmo meio e valor) e' cancelada quando,
--    sem ela, a venda bate. So' a copia lancada a mao, sem baixa de titulo
--    nem cheque: o integrado repetido pode ser cobranca em dobro de verdade.
-- 3. Troco do vale: o mesmo do troco em dinheiro, no vale maior que a
--    venda, quando o pagamento do vale nao baixou titulo.
--
-- No troco nao fica rastro. A copia cancelada fica com a justificativa
-- "Pagamento repetido no sistema antigo". O que sobrar aparece na tela
-- Divergencias (TASK-221).
--
-- Idempotente (a venda corrigida bate e sai da conta). Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '3600s';

-- vendas fechadas de antes do go-live que nao fecham (negativo = pagou a mais)
CREATE TEMP TABLE divergente ON COMMIT DROP AS
SELECT n.codnegocio,
    round(n.valortotal - coalesce(pg.pago, 0) - coalesce(pc.parcelas, 0), 2) AS diferenca
FROM mgsis.tblnegocio n
INNER JOIN mgsis.tblnaturezaoperacao nat ON (nat.codnaturezaoperacao = n.codnaturezaoperacao)
LEFT JOIN (
    SELECT codnegocio, sum(CASE
            WHEN codpagamentoorigem IS NOT NULL
                OR (codportadororigem IS NOT NULL AND codportadordestino IS NULL)
            THEN -total ELSE total END) AS pago
    FROM mgsis.tblpagamento
    WHERE estado = 'E' AND codnegocio IS NOT NULL
    GROUP BY codnegocio
) pg ON (pg.codnegocio = n.codnegocio)
LEFT JOIN (
    SELECT codnegocio, sum(valor) AS parcelas
    FROM mgsis.tblnegocioparcela
    WHERE inativo IS NULL
    GROUP BY codnegocio
) pc ON (pc.codnegocio = n.codnegocio)
WHERE n.codnegociostatus = 2
AND nat.financeiro
AND n.lancamento < '2026-10-02';

DELETE FROM divergente WHERE abs(diferenca) < 0.01;
CREATE INDEX ON divergente (codnegocio);

SELECT count(*) AS vendas_que_nao_fecham FROM divergente;

-- 1. troco em dinheiro: no maior pagamento em dinheiro da venda
CREATE TEMP TABLE troco_dinheiro ON COMMIT DROP AS
SELECT x.codnegocio, x.codpagamento, x.troco
FROM (
    SELECT d.codnegocio, p.codpagamento, p.principal, -d.diferenca AS troco,
        row_number() OVER (PARTITION BY d.codnegocio ORDER BY p.total DESC, p.codpagamento) AS ordem
    FROM divergente d
    INNER JOIN mgsis.tblpagamento p ON (p.codnegocio = d.codnegocio)
    WHERE d.diferenca < 0
    AND p.estado = 'E'
    AND p.meio = 1
    AND p.codpagamentoorigem IS NULL
) x
WHERE x.ordem = 1
AND x.troco < x.principal;

UPDATE mgsis.tblpagamento p
SET principal = p.principal - t.troco,
    total = p.total - t.troco,
    valortroco = coalesce(p.valortroco, 0) + t.troco
FROM troco_dinheiro t
WHERE t.codpagamento = p.codpagamento;

DELETE FROM divergente d USING troco_dinheiro t WHERE t.codnegocio = d.codnegocio;

SELECT count(*) AS troco_em_dinheiro FROM troco_dinheiro;

-- 2. pagamento repetido: as copias (do segundo em diante, mesmo meio e valor)
CREATE TEMP TABLE repetido ON COMMIT DROP AS
SELECT x.codnegocio, x.codpagamento, x.total, x.integrado
FROM (
    SELECT p.codnegocio, p.codpagamento, p.total,
        (p.codpixcob IS NOT NULL OR p.codpix IS NOT NULL OR p.codpagarmepedido IS NOT NULL
            OR p.codsauruspedido IS NOT NULL OR p.codliopedido IS NOT NULL
            OR EXISTS (SELECT 1 FROM mgsis.tblmovimentotitulo m WHERE m.codpagamento = p.codpagamento)
            OR EXISTS (SELECT 1 FROM mgsis.tblcheque c WHERE c.codpagamento = p.codpagamento)) AS integrado,
        row_number() OVER (PARTITION BY p.codnegocio, p.meio, p.total ORDER BY p.codpagamento) AS ordem
    FROM divergente d
    INNER JOIN mgsis.tblpagamento p ON (p.codnegocio = d.codnegocio)
    WHERE d.diferenca < 0
    AND p.estado = 'E'
    AND p.codpagamentoorigem IS NULL
) x
WHERE x.ordem > 1;

-- so' a venda que bate sem as copias, e so' se nenhuma copia e' integrada
DELETE FROM repetido r
WHERE r.codnegocio IN (
    SELECT r2.codnegocio
    FROM repetido r2
    INNER JOIN divergente d ON (d.codnegocio = r2.codnegocio)
    GROUP BY r2.codnegocio, d.diferenca
    HAVING abs(sum(r2.total) + d.diferenca) >= 0.01 OR bool_or(r2.integrado)
);

UPDATE mgsis.tblpagamento p
SET estado = 'C',
    cancelamento = now(),
    justificativa = 'Pagamento repetido no sistema antigo'
FROM repetido r
WHERE r.codpagamento = p.codpagamento;

DELETE FROM divergente d WHERE d.codnegocio IN (SELECT codnegocio FROM repetido);

SELECT count(DISTINCT codnegocio) AS vendas_com_repetido, count(*) AS copias_canceladas FROM repetido;

-- 3. troco do vale: no maior vale da venda que nao baixou titulo
CREATE TEMP TABLE troco_vale ON COMMIT DROP AS
SELECT x.codnegocio, x.codpagamento, x.troco
FROM (
    SELECT d.codnegocio, p.codpagamento, p.principal, -d.diferenca AS troco,
        row_number() OVER (PARTITION BY d.codnegocio ORDER BY p.total DESC, p.codpagamento) AS ordem
    FROM divergente d
    INNER JOIN mgsis.tblpagamento p ON (p.codnegocio = d.codnegocio)
    WHERE d.diferenca < 0
    AND p.estado = 'E'
    AND p.meio = 12
    AND p.codpagamentoorigem IS NULL
    AND NOT EXISTS (SELECT 1 FROM mgsis.tblmovimentotitulo m WHERE m.codpagamento = p.codpagamento)
) x
WHERE x.ordem = 1
AND x.troco < x.principal;

UPDATE mgsis.tblpagamento p
SET principal = p.principal - t.troco,
    total = p.total - t.troco,
    valortroco = coalesce(p.valortroco, 0) + t.troco
FROM troco_vale t
WHERE t.codpagamento = p.codpagamento;

DELETE FROM divergente d USING troco_vale t WHERE t.codnegocio = d.codnegocio;

SELECT count(*) AS troco_do_vale FROM troco_vale;

SELECT count(*) AS vendas_que_sobram FROM divergente;

COMMIT;

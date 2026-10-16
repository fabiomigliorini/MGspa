-- =====================================================================
-- Cartao da Stone gravado com o meio errado (TASK-228).
--
-- 1. Outros: com o webhook da Stone fechado (desde 23/05/2026 a rota
--    exigia login), a consulta do PDV que achava o pedido pago antes de a
--    Stone mandar a transacao gravava o pagamento como Outros, sem
--    bandeira. Com a cobranca no banco, vale a ultima nao cancelada (como
--    no PagarMeService::cartaoDoPedido): tipo, bandeira, NSU e
--    autorizacao. Sem ela, o debito/credito do pedido (o que o caixa
--    escolheu), sem bandeira. Todos de antes do go-live (12/10/2026): nao
--    mexe em periodo da maquineta.
-- 2. Pre-pago: o cartaoDoPedido traduzia pre-pago para Cartao de Credito.
--    Para a Stone pre-pago e' debito: relatorio de vendas, recebimento D+1
--    e taxa de debito. Todo o historico (decisao do Fabio em 16/10/2026);
--    o contrario (estorno, que copia o meio do original) vai junto. O
--    total do periodo da maquineta nao muda, so' a divisao debito/credito.
--
-- As notas ja emitidas nao mudam: a conciliacao DIMP mostra a diferenca
-- entre o meio do pagamento e o tPag delas.
--
-- Sem rastro na auditoria. Idempotente. Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '15min';

-- o cartao de cada pedido: a ultima cobranca nao cancelada
CREATE TEMP TABLE cartao ON COMMIT DROP AS
SELECT DISTINCT ON (pp.codpagarmepedido) pp.codpagarmepedido, pp.tipo, pp.nsu,
    left(pp.autorizacao, 40) AS autorizacao,
    CASE upper(b.bandeira)
        WHEN 'VISA' THEN 1
        WHEN 'MASTERCARD' THEN 2
        WHEN 'AMERICANEXPRESS' THEN 3
        WHEN 'SOROCRED' THEN 4
        WHEN 'DINERSCLUB' THEN 5
        WHEN 'ELO' THEN 6
        WHEN 'HIPERCARD' THEN 7
        WHEN 'AURA' THEN 8
        WHEN 'CABAL' THEN 9
        ELSE CASE WHEN b.bandeira IS NULL THEN NULL ELSE 99 END
    END AS bandeira
FROM mgsis.tblpagarmepagamento pp
LEFT JOIN mgsis.tblpagarmebandeira b ON (b.codpagarmebandeira = pp.codpagarmebandeira)
WHERE pp.codpagarmepedido IS NOT NULL
AND pp.valorcancelamento IS NULL
ORDER BY pp.codpagarmepedido, pp.codpagarmepagamento DESC;

CREATE INDEX ON cartao (codpagarmepedido);

-- 1. Outros: tipo da cobranca (1 debit, 2 credit, 3 voucher, 4 prepaid)
-- ou, sem ela, o do pedido
UPDATE mgsis.tblpagamento p
SET meio = CASE coalesce(c.tipo, ped.tipo)
        WHEN 1 THEN 4
        WHEN 4 THEN 4
        WHEN 2 THEN 3
        ELSE 99
    END,
    bandeira = coalesce(c.bandeira, p.bandeira),
    nsu = coalesce(c.nsu, p.nsu),
    autorizacao = coalesce(c.autorizacao, p.autorizacao),
    parcelas = coalesce(p.parcelas, ped.parcelas)
FROM mgsis.tblpagarmepedido ped
LEFT JOIN cartao c ON (c.codpagarmepedido = ped.codpagarmepedido)
WHERE ped.codpagarmepedido = p.codpagarmepedido
AND p.meio = 99;

-- 2. Pre-pago como credito: o pagamento do pedido
UPDATE mgsis.tblpagamento p
SET meio = 4
FROM cartao c
WHERE c.codpagarmepedido = p.codpagarmepedido
AND c.tipo = 4
AND p.meio = 3;

-- e o contrario dele
UPDATE mgsis.tblpagamento e
SET meio = 4
FROM mgsis.tblpagamento o
INNER JOIN cartao c ON (c.codpagarmepedido = o.codpagarmepedido)
WHERE o.codpagamento = e.codpagamentoorigem
AND c.tipo = 4
AND e.meio = 3;

COMMIT;

-- =====================================================================
-- Saldo gravado do portador e do periodo (doc-4, R13; TASK-39).
--
--   tblportador.saldo          — saldofinal do ultimo periodo do portador
--                                (o painel /portador le daqui)
--   tblportadorperiodo.saldofinal — passa a ficar sempre atualizado, aberto
--                                ou fechado (= saldoinicial + linhas ativas
--                                do periodo); fechar so' congela
--
-- Quem mantem os dois depois daqui e' o PortadorMovimentoService::sincronizar
-- (PortadorPeriodoService::recalcular). Esta carga so' calcula os periodos
-- abertos (os fechados ja' tem o saldofinal congelado) e copia o do ultimo
-- periodo para o portador.
--
-- Idempotente: a segunda execucao nao altera nenhuma linha. Transacional.
-- Roda depois do caixa_item.sql e antes do tipo_titulo_limpeza.sql (o ultimo
-- do go-live).
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

-- ADD COLUMN com DEFAULT constante nao reescreve a tabela, mas pega ACCESS
-- EXCLUSIVE por milissegundos; com transacao ociosa segurando lock o script
-- desiste em 5s e faz rollback. Nunca remover estes dois SET.
SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

ALTER TABLE tblportador ADD COLUMN IF NOT EXISTS saldo numeric(14,2) NOT NULL DEFAULT 0;

-- periodos abertos: saldo inicial + linhas ativas do razao
UPDATE tblportadorperiodo pp
SET saldofinal = c.saldofinal
FROM (
    SELECT
        p.codportadorperiodo,
        p.saldoinicial + coalesce((
            SELECT sum(m.valor)
            FROM tblportadormovimento m
            WHERE m.codportadorperiodo = p.codportadorperiodo
            AND m.inativo IS NULL
        ), 0) AS saldofinal
    FROM tblportadorperiodo p
    WHERE p.fechamento IS NULL
) c
WHERE c.codportadorperiodo = pp.codportadorperiodo
AND pp.saldofinal IS DISTINCT FROM c.saldofinal;

-- portador: o saldo final do ultimo periodo (0 sem periodo)
UPDATE tblportador po
SET saldo = c.saldo
FROM (
    SELECT
        po2.codportador,
        coalesce((
            SELECT pp.saldofinal
            FROM tblportadorperiodo pp
            WHERE pp.codportador = po2.codportador
            ORDER BY pp.inicio DESC, pp.codportadorperiodo DESC
            LIMIT 1
        ), 0) AS saldo
    FROM tblportador po2
) c
WHERE c.codportador = po.codportador
AND po.saldo IS DISTINCT FROM c.saldo;

COMMIT;

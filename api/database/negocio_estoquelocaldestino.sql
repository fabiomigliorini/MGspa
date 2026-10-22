-- =====================================================================
-- Loja destino nos negocios antigos (TASK-55, etapa 6 do doc-6).
--
-- O job de estoque do MGLara passa a gerar a entrada no destino SO' pelo
-- tblnegocio.codestoquelocaldestino (antes achava pela filial da pessoa).
-- Este script preenche o campo no historico, para um reprocessamento de
-- estoque nao apagar a entrada que ja' existe:
--   1. pelo movimento de destino que ja' existe (transferencias e vendas
--      entre empresas do grupo, naturezas 15, 1, 19...);
--   2. transferencias de saida que sobraram: pessoa -> filial (so' quando a
--      pessoa e' de uma filial so') -> local (so' quando a filial tem um so').
-- As transferencias para pessoa que nao e' filial continuam nulas (ja' nao
-- geravam entrada).
--
-- DADOS. ORDEM: no go-live, ANTES da publicacao do MGLara novo.
-- Reaplicavel (so' mexe onde o campo esta' nulo). Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '60min';

-- 1. pelo movimento de destino ja' gerado pelo job
WITH destino AS (
    SELECT DISTINCT ON (npb.codnegocio) npb.codnegocio, elpv.codestoquelocal
    FROM tblestoquemovimento m
    INNER JOIN tblnegocioprodutobarra npb ON (npb.codnegocioprodutobarra = m.codnegocioprodutobarra)
    INNER JOIN tblnegocio n ON (n.codnegocio = npb.codnegocio)
    INNER JOIN tblestoquemes em ON (em.codestoquemes = m.codestoquemes)
    INNER JOIN tblestoquesaldo es ON (es.codestoquesaldo = em.codestoquesaldo)
    INNER JOIN tblestoquelocalprodutovariacao elpv
        ON (elpv.codestoquelocalprodutovariacao = es.codestoquelocalprodutovariacao)
    WHERE m.codestoquemovimentoorigem IS NOT NULL
    AND n.codestoquelocaldestino IS NULL
    AND elpv.codestoquelocal <> n.codestoquelocal
    ORDER BY npb.codnegocio, m.codestoquemovimento
)
UPDATE tblnegocio n
SET codestoquelocaldestino = destino.codestoquelocal
FROM destino
WHERE n.codnegocio = destino.codnegocio
AND n.codestoquelocaldestino IS NULL;

-- 2. transferencias de saida restantes: filial unica da pessoa, local unico da filial
UPDATE tblnegocio n
SET codestoquelocaldestino = el.codestoquelocal
FROM tblnaturezaoperacao nat, tblfilial f, tblestoquelocal el
WHERE nat.codnaturezaoperacao = n.codnaturezaoperacao
AND nat.transferencia
AND nat.codoperacao = 2
AND n.codestoquelocaldestino IS NULL
AND f.codpessoa = n.codpessoa
AND el.codfilial = f.codfilial
AND el.codestoquelocal <> n.codestoquelocal
AND (SELECT count(*) FROM tblfilial f2 WHERE f2.codpessoa = n.codpessoa) = 1
AND (SELECT count(*) FROM tblestoquelocal e2 WHERE e2.codfilial = f.codfilial) = 1;

-- 3. conferencia: por natureza, com e sem loja destino
SELECT n.codnaturezaoperacao, nat.naturezaoperacao,
    count(*) FILTER (WHERE n.codestoquelocaldestino IS NOT NULL) AS comdestino,
    count(*) FILTER (WHERE n.codestoquelocaldestino IS NULL) AS semdestino
FROM tblnegocio n
INNER JOIN tblnaturezaoperacao nat ON (nat.codnaturezaoperacao = n.codnaturezaoperacao)
WHERE n.codestoquelocaldestino IS NOT NULL OR (nat.transferencia AND nat.codoperacao = 2)
GROUP BY 1, 2
ORDER BY 1;

COMMIT;

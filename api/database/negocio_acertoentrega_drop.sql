-- =====================================================================
-- TASK-55 — Limpa o controle de entrega antigo do negocio (MGsis), parado
-- desde 2014, antes do controle novo (tblnegocioentrega):
--   entrega         — continua: passa a ser "tem entrega a controlar". Os
--                     4.302 true de 2012–2014 voltam a false, senao viram
--                     entregas pendentes na lista nova.
--   acertoentrega, codusuarioacertoentrega — saem (nenhum preenchido).
--
-- ORDEM: depois da publicacao do codigo da api, do MGLara e do negocios que
-- deixou de ler acertoentrega/codusuarioacertoentrega.
--
-- Reaplicavel. Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

-- conferencia: so' negocios ate' 2014 estao com entrega = true
SELECT extract(year FROM lancamento) AS ano, count(*) AS entrega_true
FROM tblnegocio
WHERE entrega
GROUP BY 1
ORDER BY 1;

UPDATE tblnegocio SET entrega = false
WHERE entrega AND lancamento < '2015-01-01';

ALTER TABLE tblnegocio
    DROP COLUMN IF EXISTS acertoentrega,
    DROP COLUMN IF EXISTS codusuarioacertoentrega;

COMMIT;

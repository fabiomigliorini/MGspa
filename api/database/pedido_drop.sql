-- =====================================================================
-- TASK-55 — Remove as tabelas do Pedido, que nasceu para requisicao entre
-- lojas e morreu sem uso (0 linhas, sem tela). A requisicao nasce como
-- dominio proprio (backlog/docs/doc-6); pedido de compra, quando vier, e'
-- outro contexto.
--   tblnegocioprodutobarrapedidoitem, tblpedidoitem, tblpedido
--
-- ORDEM: depois da publicacao do codigo que tirou Mg\Pedido e as rotas
-- v1/pedido. Aborta se alguma tabela tiver linha.
--
-- Reaplicavel (IF EXISTS). Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

DO $$
DECLARE
    t text;
    n bigint;
BEGIN
    FOREACH t IN ARRAY ARRAY['tblnegocioprodutobarrapedidoitem', 'tblpedidoitem', 'tblpedido'] LOOP
        IF to_regclass(t) IS NOT NULL THEN
            EXECUTE format('SELECT count(*) FROM %I', t) INTO n;
            IF n > 0 THEN
                RAISE EXCEPTION '% tem % linha(s); nada foi apagado', t, n;
            END IF;
        END IF;
    END LOOP;
END $$;

DROP TABLE IF EXISTS tblnegocioprodutobarrapedidoitem;
DROP TABLE IF EXISTS tblpedidoitem;
DROP TABLE IF EXISTS tblpedido;

COMMIT;

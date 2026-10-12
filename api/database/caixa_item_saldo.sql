-- =====================================================================
-- Saldo gravado do item do caixa (doc-4, "Itens do caixa" e "Itens de
-- parceiro"; TASK-39 #23): a lista de Itens do Caixa mostra o saldo de cada
-- item sem abrir um por um.
--
--   tblcaixaitem.saldo           — cedula (chips): o valor em caixa, somando
--                                  todos os caixas; maquineta de parceiro: o
--                                  que devemos ao parceiro.
--   tblcaixaitem.saldoquantidade — cedula: a quantidade em caixa; maquineta:
--                                  null.
--
-- O sistema recalcula a cada acao de item (CaixaItemService::recalcularSaldo).
-- Depois de rodar, os itens que ja' existem ficam zerados ate' o botao
-- "Recalcular saldos" da lista de Itens do Caixa.
--
-- Idempotente. Transacional. Roda no go-live depois do
-- caixa_item_maquineta.sql.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

ALTER TABLE tblcaixaitem
    ADD COLUMN IF NOT EXISTS saldo numeric(14,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS saldoquantidade integer;

COMMIT;

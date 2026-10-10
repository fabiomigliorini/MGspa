-- =====================================================================
-- Pagamento = fato, amarracao = outra coisa (conceito do Fabio, 09/10/2026;
-- TASK-188). SOMENTE DADOS DE CADASTRO, idempotente e transacional.
--
--   1. Portador "Encontro de Contas": encontro de contas (titulos que se
--      anulam) e compensacao sem dinheiro gravam aqui. Reaproveita o
--      pseudoportador 202016 (Programacao Pagamentos), onde a liquidacao
--      antiga ja' guardava as compensacoes: reativa e renomeia.
--   2. Papeis nos portadores (tblportadorusuario) equivalentes ao acesso de
--      hoje, para ninguem travar no go-live: toda permissao do dinheiro
--      passa a ser o papel do usuario no portador (doc-4). So' insere o que
--      falta; nunca rebaixa nem apaga papel ja' cadastrado.
--        Caixa da filial      -> operador na gaveta (portador do PDV) da filial
--        Gerente da filial    -> gestor na gaveta, no cofre e no troco (especie)
--                                da filial
--        Financeiro/Cobranca  -> gestor nos bancos, adquirentes, cartoes da
--                                empresa, Caixa Financeiro, Carteira e
--                                Encontro de Contas
--      Administrador ja' e' gestor em tudo (sem linha).
--
-- Roda depois do auditoria.sql e do ocorrencia.sql. Revisar a secao 2 antes
-- de rodar em producao (conferir os grupos e filiais em tblgrupousuariousuario).
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

-- ---------------------------------------------------------------------
-- 1. Portador Encontro de Contas
-- ---------------------------------------------------------------------
UPDATE tblportador
   SET portador = 'Encontro de Contas',
       inativo = NULL,
       alteracao = now()
 WHERE codportador = 202016
   AND (portador <> 'Encontro de Contas' OR inativo IS NOT NULL);

COMMIT;

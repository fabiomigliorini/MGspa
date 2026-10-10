-- =====================================================================
-- Pagamento = fato, amarracao = outra coisa (conceito do Fabio, 09/10/2026;
-- TASK-188). SOMENTE DADOS DE CADASTRO, idempotente e transacional.
--
--   1. Portador "Encontro de Contas": encontro de contas (titulos que se
--      anulam) e compensacao sem dinheiro gravam aqui. Reaproveita o
--      pseudoportador 202016 (Programacao Pagamentos), onde a liquidacao
--      antiga ja' guardava as compensacoes: reativa e renomeia.
--   2. Papeis nos portadores (tblportadorusuario) que as regras novas pedem,
--      para ninguem travar no go-live: toda permissao do dinheiro passa a
--      ser o papel do usuario no portador (doc-4) — receber num portador =
--      depositante; tirar, desamarrar, cancelar, corrigir = operador; alterar
--      data = gestor. O PDV so' pre-seleciona a gaveta. Completa o que o
--      portador_movimento_tipo.sql ja' cadastrou (caixa operador na gaveta,
--      gerente gestor na especie da filial, financeiro gestor no resto). So'
--      insere o par portador+usuario que nao existe; nunca rebaixa nem apaga.
--        Caixa da filial    -> depositante nas adquirentes (cartao), na
--                              Carteira (cheque) e nos bancos da filial (PIX QR)
--        Gerente da filial  -> depositante nas adquirentes, na Carteira e nos
--                              bancos da filial
--        Cobranca           -> gestor como o Financeiro: bancos, adquirentes,
--                              cartoes da empresa, Caixa Financeiro, Carteira
--        Financeiro/Cobranca-> gestor no Encontro de Contas e na Carteira
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

-- ---------------------------------------------------------------------
-- 2. Papeis
-- ---------------------------------------------------------------------
INSERT INTO tblportadorusuario (codportador, codusuario, papel)
SELECT r.codportador, r.codusuario, (ARRAY['D', 'O', 'G'])[max(r.nivel)]
FROM (
    SELECT po.codportador, guu.codusuario,
        CASE
            -- caixa e gerente: recebem cartao, cheque e PIX QR
            WHEN gu.grupousuario IN ('Caixa', 'Gerente') AND po.tipo = 'A' THEN 1
            WHEN gu.grupousuario IN ('Caixa', 'Gerente') AND po.codportador = 999 THEN 1
            WHEN gu.grupousuario IN ('Caixa', 'Gerente') AND po.tipo = 'B'
                AND guu.codfilial = po.codfilial THEN 1
            -- cobranca como o financeiro
            WHEN gu.grupousuario = 'Cobranca' AND (po.tipo <> 'E' OR po.codportador = 100) THEN 3
            -- financeiro: encontro de contas e carteira
            WHEN gu.grupousuario = 'Financeiro' AND po.codportador IN (202016, 999) THEN 3
        END AS nivel
    FROM tblportador po
    CROSS JOIN tblgrupousuariousuario guu
    JOIN tblgrupousuario gu ON gu.codgrupousuario = guu.codgrupousuario
    JOIN tblusuario u ON u.codusuario = guu.codusuario AND u.inativo IS NULL
    WHERE gu.grupousuario IN ('Caixa', 'Gerente', 'Cobranca', 'Financeiro')
      AND (po.inativo IS NULL OR po.codportador = 202016)
) r
WHERE r.nivel IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tblportadorusuario x
      WHERE x.codportador = r.codportador AND x.codusuario = r.codusuario
  )
GROUP BY r.codportador, r.codusuario;

COMMIT;

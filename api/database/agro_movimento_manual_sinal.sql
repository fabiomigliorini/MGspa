-- TASK-170 (D4): ajuste manual de RETIRADA de silo lancado antes da correcao
-- gravava positivo e SOMAVA no saldo. Rodar em PROD depois do deploy do api.
-- O `liquido > 0` impede que uma 2a rodada desinverta.

-- 1) conferir antes (levar a lista pra quem lancou):
SELECT codmovimentograo, data, codunidadearmazenadora, bruto, desconto, liquido, observacao, codusuariocriacao
  FROM tblmovimentograo
 WHERE manual AND contatipo = 'UNIDADE' AND papel = 'ORIGEM' AND liquido > 0 AND inativo IS NULL;

-- 2) inverter (so depois da lista conferida):
-- UPDATE tblmovimentograo
--    SET bruto = -bruto, desconto = -desconto, liquido = -liquido
--  WHERE manual AND contatipo = 'UNIDADE' AND papel = 'ORIGEM' AND liquido > 0;

-- ============================================================
-- Agro: normaliza a fixação de preço — FIXO ganha fixação-espelho.
--
-- Antes: contrato FIXO guardava o preço só no próprio contrato (tblcontrato.preco)
-- e NÃO tinha linha em tblcontratofixacao; só FIXAR/BARTER fixavam à mão. Isso
-- obrigava todo cálculo de preço/fixado a tratar FIXO como caso especial.
--
-- Agora: todo contrato FIXO mantém UMA fixação "automática" (quantidade cheia,
-- preço/moeda do contrato), gerenciada pelo ContratoService. Assim fixado e
-- preço médio rodam uniformemente sobre tblcontratofixacao, sem `if tipo==FIXO`.
--
-- A coluna `automatico` marca a linha-espelho: o service apaga/recria só as
-- automáticas, nunca toca nas fixações digitadas à mão (FIXAR/BARTER).
--
-- Rodar no dev. PostgreSQL. Idempotente.
-- ============================================================

ALTER TABLE tblcontratofixacao
  ADD COLUMN IF NOT EXISTS automatico boolean NOT NULL DEFAULT false; -- linha-espelho do FIXO

-- Backfill: cria a fixação-espelho dos contratos FIXO que ainda não têm.
-- precoreal = preço (FIXO é em R$; USD sem dólar travado fica 1:1, mesmo limite
-- de hoje). Idempotente: só insere onde ainda não existe espelho.
INSERT INTO tblcontratofixacao
  (codcontrato, data, quantidade, preco, moeda, dolar, precoreal, automatico, criacao, alteracao)
SELECT
  c.codcontrato,
  COALESCE(c.dataembarque, CURRENT_DATE),
  c.quantidade,
  COALESCE(c.preco, 0),
  COALESCE(c.moeda, 'BRL'),
  NULL,
  COALESCE(c.preco, 0),
  true,
  now(),
  now()
FROM tblcontrato c
WHERE c.tipo = 'FIXO'
  AND NOT EXISTS (
    SELECT 1 FROM tblcontratofixacao f
    WHERE f.codcontrato = c.codcontrato AND f.automatico = true
  );

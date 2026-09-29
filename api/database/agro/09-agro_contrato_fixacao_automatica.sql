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
--
-- GUARDA: so roda antes da refatoracao do contrato (passo 14 dropa tblcontrato.preco).
-- Enquanto isso, roda sempre: o INSERT so cria o espelho que falta. Importante na
-- PROD com contratos reais: e ESTE passo que guarda o preco do FIXO numa fixacao
-- antes do 14.
-- Bloco unico (atomico); sem BEGIN/COMMIT pra nao deixar transacao aberta no DBeaver.
-- =====================================================================

SET lock_timeout = '5s';

DO $agro$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblcontrato' AND column_name = 'preco') THEN
    RAISE NOTICE '09 contrato_fixacao_automatica: pulado (contrato ja refatorado)';
    RETURN;
  END IF;

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

  RAISE NOTICE '09 contrato_fixacao_automatica: aplicado';
END
$agro$;

-- ============================================================
-- Agro: snapshot dos impostos travado na fixação de preço.
--
-- Antes: o preço LÍQUIDO da fixação era recalculado na leitura
-- (ContratoFixacaoResource) a partir da config de tributos (tblculturatributo)
-- + a competência da UPF na data — ou seja, o líquido mudava se a config/UPF
-- mudasse depois, e o operador não conseguia DIGITAR a alíquota/UPF efetiva.
--
-- Agora: ao criar/editar a fixação o operador informa (ou ajusta) as alíquotas
-- e a UPF no modal de impostos; o resultado é GRAVADO junto da fixação. O
-- líquido fica travado no momento da fixação (auditável, imune a mudança
-- posterior de config/UPF) — previsibilidade pro produtor.
--
--   precoliquido = R$/sc líquido travado (bruto − total das deduções)
--   totaldeducao = R$/sc somado das deduções
--   tributos     = snapshot JSON das linhas [{codtributo, codigo, descricao,
--                  base, percentual, upf, valor}] usadas no cálculo
--
-- Colunas NULL = fixação antiga / espelho automático do FIXO: o resource cai
-- no cálculo on-the-fly (retrocompatível).
--
-- Rodar no dev. PostgreSQL. Idempotente (ADD COLUMN IF NOT EXISTS).
-- ============================================================
--
-- GUARDA: pula se tributos ja existe. precoliquido/totaldeducao so nascem antes do
-- passo 21 (que os dropa) — senao voltavam como colunas orfas.
-- Bloco unico (atomico); sem BEGIN/COMMIT pra nao deixar transacao aberta no DBeaver.
-- =====================================================================

SET lock_timeout = '5s';

DO $agro$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblcontratofixacao' AND column_name = 'tributos') THEN
    RAISE NOTICE '18 fixacao_impostos: pulado (tributos ja existe)';
    RETURN;
  END IF;

  ALTER TABLE tblcontratofixacao
    ADD COLUMN IF NOT EXISTS tributos jsonb NULL;
  COMMENT ON COLUMN tblcontratofixacao.tributos IS
    'Snapshot JSON das linhas de imposto usadas no cálculo do líquido desta fixação.';

  -- precoliquido/totaldeducao so antes do passo 21 (que os converte e dropa).
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblcontratofixacao' AND column_name = 'moeda') THEN
    ALTER TABLE tblcontratofixacao
      ADD COLUMN IF NOT EXISTS precoliquido numeric NULL,
      ADD COLUMN IF NOT EXISTS totaldeducao numeric NULL;
    COMMENT ON COLUMN tblcontratofixacao.precoliquido IS
      'R$/sc líquido travado no momento da fixação (bruto − total das deduções). NULL = calcula on-the-fly.';
    COMMENT ON COLUMN tblcontratofixacao.totaldeducao IS
      'R$/sc somado das deduções (FETHAB/IAGRO/SENAR/FUNRURAL) no momento da fixação.';
  END IF;

  RAISE NOTICE '18 fixacao_impostos: aplicado';
END
$agro$;

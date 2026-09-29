-- =====================================================================
-- AGRO — Recebimentos por fixação + quitação com diferença
--
-- Refatora tblcontratopagamento pra um RECEBIMENTO puro (dinheiro que entrou),
-- ancorado numa fixação (1 fixação : N recebimentos):
--   - a "parcela prevista" some: o previsto é o LÍQUIDO da fixação
--   - o registro PASSA A SER o recebimento (data/valor = o recebido)
--   - saem: codcontrato (derivável), modo/sacas, datarecebido/valorrecebido
--     (viram data/valor), forma, cotacao/cotacaorecebido (câmbio vive na trava)
--
-- + tblcontratofixacao.quitado (timestamp): marca a fixação como RECEBIDA mesmo
--   quando o recebido não bate no centavo com o líquido (diferencinha de imposto).
--   A diferença = recebido − líquido fica derivada/visível, não trava o encerrar.
--
-- Só rec. reais ANCORADOS numa fixação viram recebimento; previsto-sem-receber
-- e sem-fixação são descartados (ficam no backup).
--
-- Schema mgsis. Convenções tbl<nome>/cod<nome>, auditoria MgModel, inativo.
-- Estratégia backup->altera->restaura (prod pode diferir). Idempotente: a
-- refatoração só roda enquanto a coluna legada `datarecebido` existir.
-- =====================================================================
--
-- GUARDA: so roda enquanto tblcontratopagamento.datarecebido existe. O passo 11
-- nao recria mais essa coluna, entao o DELETE daqui nao re-arma. O backup de uma
-- rodada anterior nao e mais sobrescrito.
-- Bloco unico (atomico); sem BEGIN/COMMIT pra nao deixar transacao aberta no DBeaver.
-- =====================================================================

SET lock_timeout = '5s';

DO $agro$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblcontratopagamento' AND column_name = 'datarecebido') AND EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblcontratofixacao' AND column_name = 'quitado') THEN
    RAISE NOTICE '22 contrato_recebimento: pulado (pagamento ja refatorado)';
    RETURN;
  END IF;

  -- ===================== 1) quitado na fixação =========================
  -- Marca "recebida" (encerra o saldo). Independe de bater no centavo.
  ALTER TABLE tblcontratofixacao ADD COLUMN IF NOT EXISTS quitado timestamp;

  -- ============ 2) Recebimento puro (so enquanto datarecebido existir) ======
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblcontratopagamento' AND column_name = 'datarecebido') THEN
    -- 2.0) BACKUP completo do estado atual.
    -- Backup de uma rodada anterior interrompida NAO e sobrescrito.
    IF to_regclass('tblcontratopagamento_bkp_recebimento') IS NULL THEN
      CREATE TABLE tblcontratopagamento_bkp_recebimento AS SELECT * FROM tblcontratopagamento;
    END IF;

    -- 2.1) Só recebimento REAL e ancorado numa fixação sobrevive. O resto
    --      (parcela prevista sem receber, ou sem vínculo de fixação) é descartado.
    DELETE FROM tblcontratopagamento
     WHERE datarecebido IS NULL OR codcontratofixacao IS NULL;

    -- 2.2) O registro passa a SER o recebimento: data/valor = o recebido.
    UPDATE tblcontratopagamento
       SET data = datarecebido,
           valor = coalesce(valorrecebido, valor);

    -- 2.3) Remove constraints/colunas do legado.
    ALTER TABLE tblcontratopagamento DROP CONSTRAINT IF EXISTS chk_contratopagamento_forma;
    ALTER TABLE tblcontratopagamento DROP CONSTRAINT IF EXISTS chk_contratopagamento_modo;
    ALTER TABLE tblcontratopagamento DROP CONSTRAINT IF EXISTS tblcontratopagamento_codcontrato_fkey;

    -- Recebimento agora SEMPRE pertence a uma fixação.
    ALTER TABLE tblcontratopagamento ALTER COLUMN codcontratofixacao SET NOT NULL;

    ALTER TABLE tblcontratopagamento DROP COLUMN IF EXISTS codcontrato;
    ALTER TABLE tblcontratopagamento DROP COLUMN IF EXISTS modo;
    ALTER TABLE tblcontratopagamento DROP COLUMN IF EXISTS sacas;
    ALTER TABLE tblcontratopagamento DROP COLUMN IF EXISTS datarecebido;
    ALTER TABLE tblcontratopagamento DROP COLUMN IF EXISTS valorrecebido;
    ALTER TABLE tblcontratopagamento DROP COLUMN IF EXISTS forma;
    ALTER TABLE tblcontratopagamento DROP COLUMN IF EXISTS cotacao;
    ALTER TABLE tblcontratopagamento DROP COLUMN IF EXISTS cotacaorecebido;

    RAISE NOTICE 'tblcontratopagamento refatorada. Backup em tblcontratopagamento_bkp_recebimento (dropar quando validar).';
  END IF;

  RAISE NOTICE '22 contrato_recebimento: aplicado';
END
$agro$;

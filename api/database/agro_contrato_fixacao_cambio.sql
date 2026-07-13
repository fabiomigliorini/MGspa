-- =====================================================================
-- AGRO — Fixacao: travamento de cambio (parcial/total) + totais gravados
--
-- Repensa tblcontratofixacao pro modelo real:
--   - fixacao = evento COMERCIAL (data, vencimento, sacas, moeda, preco)
--   - travamento de cambio = tabela filha tblcontratofixacaocambio
--       1 fixacao : N travas   (total = 1 linha; parcial = N linhas)
--   - 4 totais GRAVADOS na fixacao, recalculados pelo backend a cada
--     operacao de trava / edicao da fixacao:
--       totalmoeda = quantidade x preco          (na moeda da fixacao)
--       saldomoeda = totalmoeda - SUM(travado)    (moeda ainda a travar)
--       totalbrl   = SUM(valor x cotacao)         (travado, bruto em R$)
--       liquidobrl = totalbrl - impostos          (travado, liquido em R$)
--
-- Sai o que era snapshot/derivado por saca:
--   moeda (varchar->codmoeda FK inteira), dolar, precoreal, isentofethab,
--   precoliquido, totaldeducao.
--
-- ESTRATEGIA BACKUP -> ALTERA -> RESTAURA (a producao pode diferir do dev):
--   1. snapshot COMPLETO do estado atual em tblcontratofixacao_bkp_cambio
--   2. altera o schema (novas colunas, dropa legado)
--   3. restaura/backfill lendo do BACKUP (nao das colunas dropadas)
--   O backup PERMANECE apos a migracao p/ conferencia; dropar manualmente
--   quando validado:  DROP TABLE tblcontratofixacao_bkp_cambio;
--
-- Schema mgsis. Convencoes tbl<nome>/cod<nome>, auditoria MgModel, inativo.
-- Idempotente: a migracao so roda enquanto a coluna antiga `moeda` existir.
-- =====================================================================

BEGIN;

-- ===================== 1) Tabela das travas de cambio ================
CREATE TABLE IF NOT EXISTS tblcontratofixacaocambio (
  codcontratofixacaocambio  bigserial     PRIMARY KEY,
  codcontratofixacao        integer       NOT NULL
      REFERENCES tblcontratofixacao(codcontratofixacao) ON DELETE CASCADE,
  data                      date          NOT NULL,          -- data da trava
  valor                     numeric(14,2) NOT NULL,          -- travado, na moeda da fixacao
  cotacao                   numeric(10,4) NOT NULL,          -- R$/moeda combinado
  observacao                varchar(120),
  inativo                   timestamp,
  criacao                   timestamp,
  alteracao                 timestamp,
  codusuariocriacao         integer,
  codusuarioalteracao       integer,
  CONSTRAINT chk_contratofixacaocambio_valor   CHECK (valor > 0),
  CONSTRAINT chk_contratofixacaocambio_cotacao CHECK (cotacao > 0)
);
CREATE INDEX IF NOT EXISTS ix_contratofixacaocambio_codcontratofixacao
  ON tblcontratofixacaocambio (codcontratofixacao);

-- ===================== 2) Novas colunas na fixacao ===================
-- Idempotentes (IF NOT EXISTS); nao dependem das colunas antigas.
ALTER TABLE tblcontratofixacao ADD COLUMN IF NOT EXISTS codmoeda       integer;              -- FK inteira (restaurada abaixo)
ALTER TABLE tblcontratofixacao ADD COLUMN IF NOT EXISTS datavencimento date;                 -- quando recebe (fluxo de caixa)
ALTER TABLE tblcontratofixacao ADD COLUMN IF NOT EXISTS totalmoeda numeric(14,2) NOT NULL DEFAULT 0;
ALTER TABLE tblcontratofixacao ADD COLUMN IF NOT EXISTS saldomoeda numeric(14,2) NOT NULL DEFAULT 0;
ALTER TABLE tblcontratofixacao ADD COLUMN IF NOT EXISTS totalbrl   numeric(14,2) NOT NULL DEFAULT 0;
ALTER TABLE tblcontratofixacao ADD COLUMN IF NOT EXISTS liquidobrl numeric(14,2) NOT NULL DEFAULT 0;

-- ============ 3) Backup -> restaura (guardado/idempotente) ===========
-- So executa enquanto a coluna legada `moeda` existir. Numa 2a rodada ela
-- ja foi dropada -> o bloco inteiro e pulado (o backup da 1a rodada fica).
DO $migra$
DECLARE
  v_real integer;
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = 'mgsis' AND table_name = 'tblcontratofixacao' AND column_name = 'moeda'
  ) THEN
    RAISE NOTICE 'tblcontratofixacao ja migrada (coluna moeda ausente) — nada a fazer.';
    RETURN;
  END IF;

  SELECT codmoeda INTO v_real FROM tblmoeda WHERE iso = 'BRL' LIMIT 1;

  -- 3.0) BACKUP completo do estado atual (com TODAS as colunas de hoje).
  --      Fonte do restore; sobrevive aos DROPs abaixo.
  DROP TABLE IF EXISTS tblcontratofixacao_bkp_cambio;
  CREATE TABLE tblcontratofixacao_bkp_cambio AS SELECT * FROM tblcontratofixacao;

  -- 3.1) Trava LEGADA (coluna dolar) -> nova tabela, lida do BACKUP. Fixacao
  --      estrangeira com cotacao total travada vira 1 linha (valor = total em
  --      moeda, cotacao = dolar). Idempotente (NOT EXISTS).
  INSERT INTO tblcontratofixacaocambio (codcontratofixacao, data, valor, cotacao, criacao, alteracao)
  SELECT b.codcontratofixacao, b.data, round(b.quantidade * b.preco, 2), b.dolar, now(), now()
    FROM tblcontratofixacao_bkp_cambio b
   WHERE b.dolar IS NOT NULL AND b.moeda <> 'BRL'
     AND NOT EXISTS (SELECT 1 FROM tblcontratofixacaocambio c
                      WHERE c.codcontratofixacao = b.codcontratofixacao);

  -- 3.2) RESTAURA codmoeda a partir do iso do backup (rede de seguranca -> Real).
  UPDATE tblcontratofixacao f SET codmoeda = m.codmoeda
    FROM tblcontratofixacao_bkp_cambio b
    JOIN tblmoeda m ON m.iso = b.moeda
   WHERE b.codcontratofixacao = f.codcontratofixacao AND f.codmoeda IS NULL;
  UPDATE tblcontratofixacao SET codmoeda = v_real WHERE codmoeda IS NULL;

  -- 3.3) RESTAURA os 4 totais a partir do backup + travas migradas.
  --      BRL = firme cheio; estrangeira = so a parte travada.
  UPDATE tblcontratofixacao f
     SET totalmoeda = round(b.quantidade * b.preco, 2),
         saldomoeda = CASE WHEN b.moeda = 'BRL' THEN 0
                           ELSE round(b.quantidade * b.preco - coalesce(agg.travadomoeda, 0), 2) END,
         totalbrl   = CASE WHEN b.moeda = 'BRL'
                             THEN round(b.quantidade * coalesce(b.precoreal, b.preco), 2)
                           ELSE round(coalesce(agg.travadobrl, 0), 2) END,
         liquidobrl = CASE WHEN b.moeda = 'BRL'
                             THEN round(b.quantidade * coalesce(b.precoliquido, b.precoreal, b.preco), 2)
                           WHEN b.precoliquido IS NOT NULL AND coalesce(agg.travadobrl, 0) > 0
                             THEN round(b.quantidade * b.precoliquido, 2)
                           ELSE 0 END
    FROM tblcontratofixacao_bkp_cambio b
    LEFT JOIN (
      SELECT codcontratofixacao,
             coalesce(sum(valor), 0)             AS travadomoeda,
             coalesce(sum(valor * cotacao), 0)   AS travadobrl
        FROM tblcontratofixacaocambio WHERE inativo IS NULL
       GROUP BY codcontratofixacao
    ) agg ON agg.codcontratofixacao = b.codcontratofixacao
   WHERE b.codcontratofixacao = f.codcontratofixacao;

  -- 3.4) codmoeda: NOT NULL + default dinamico (Real) + FK inteira.
  ALTER TABLE tblcontratofixacao ALTER COLUMN codmoeda SET NOT NULL;
  EXECUTE format('ALTER TABLE tblcontratofixacao ALTER COLUMN codmoeda SET DEFAULT %s', v_real);
  IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'tblcontratofixacao_codmoeda_fkey') THEN
    ALTER TABLE tblcontratofixacao
      ADD CONSTRAINT tblcontratofixacao_codmoeda_fkey
      FOREIGN KEY (codmoeda) REFERENCES tblmoeda(codmoeda);
  END IF;

  -- 3.5) Remove o legado/derivado (info ja preservada no backup + campos novos).
  ALTER TABLE tblcontratofixacao DROP CONSTRAINT IF EXISTS tblcontratofixacao_moeda_fkey;
  ALTER TABLE tblcontratofixacao DROP COLUMN IF EXISTS moeda;
  ALTER TABLE tblcontratofixacao DROP COLUMN IF EXISTS dolar;
  ALTER TABLE tblcontratofixacao DROP COLUMN IF EXISTS precoreal;
  ALTER TABLE tblcontratofixacao DROP COLUMN IF EXISTS isentofethab;
  ALTER TABLE tblcontratofixacao DROP COLUMN IF EXISTS precoliquido;
  ALTER TABLE tblcontratofixacao DROP COLUMN IF EXISTS totaldeducao;

  RAISE NOTICE 'Migracao concluida. Backup em tblcontratofixacao_bkp_cambio (dropar quando validar).';
END
$migra$;

COMMIT;

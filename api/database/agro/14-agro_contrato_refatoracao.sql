-- =====================================================================
-- Refatoracao do contrato de graos (agro) — limpeza da tblcontrato.
--
--  - PRECO/MOEDA saem do contrato: precificacao vive SO na fixacao
--    (tblcontratofixacao ja tem preco/moeda/dolar/precoreal). O FIXO deixa de
--    ter espelho automatico — vira "criar a fixacao cheia na assinatura".
--  - ISENTOFETHAB passa pra fixacao (cada evento de preco com seu regime).
--  - TIPO (FIXO/FIXAR/BARTER) deixa de ser coluna: e derivado.
--      barter  = contrato tem pagamento com forma=BARTER
--      senao   = fixado >= quantidade (e quantidade nao nula) ? FIXO : FIXAR
--  - VOLUME EM ABERTO deixa de ser flag: quantidade NULL = em aberto
--    (leva o saldo do silo; sem teto de carregamento no embarque).
--  - NF (natureza/pessoa/observacao) sai do contrato p/ tblcontratonota:
--    operacao triangular gera N notas por carga, em sequencia, cada uma
--    podendo referenciar a chave de outra (refNFe) via FK-pai.
--  - FORMA de pagamento (CONTA|BARTER): settlement vive no pagamento.
--
-- Dev esta vazio (pre go-live), entao e redesenho limpo. Reaplicavel.
-- Rodar DEPOIS dos demais agro_contrato_*.sql no go-live.
-- =====================================================================
--
-- GUARDA: so roda enquanto tblcontrato.tipo existe. Rodar de novo recriava
-- tblcontratopagamento.forma, que o passo 22 remove.
-- BACKFILL (29/09/2026): antes de dropar, tipo BARTER vira barter=true,
-- volumeemaberto vira quantidade NULL, NF (natureza/pessoa/obs) vira a 1a linha
-- de tblcontratonota e isentofethab desce pras fixacoes. Sem isso a PROD perdia
-- esses dados dos contratos ja lancados.
-- Bloco unico (atomico); sem BEGIN/COMMIT pra nao deixar transacao aberta no DBeaver.
-- =====================================================================

SET lock_timeout = '5s';

DO $agro$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblcontrato' AND column_name = 'tipo') THEN
    RAISE NOTICE '14 contrato_refatoracao: pulado (contrato ja refatorado)';
    RETURN;
  END IF;

  -- 0) tblcontratonota: plano de emissao de NF por contrato (triangular).
  CREATE TABLE IF NOT EXISTS tblcontratonota (
    codcontratonota      serial PRIMARY KEY,
    codcontrato          integer NOT NULL REFERENCES tblcontrato(codcontrato) ON DELETE CASCADE,
    ordem                smallint NOT NULL DEFAULT 1,
    codnaturezaoperacao  integer REFERENCES tblnaturezaoperacao(codnaturezaoperacao),
    codpessoanf          integer REFERENCES tblpessoa(codpessoa),
    codcontratonotapai   integer REFERENCES tblcontratonota(codcontratonota),
    observacaonf         text,
    inativo              timestamp without time zone,
    criacao              timestamp without time zone,
    alteracao            timestamp without time zone,
    codusuariocriacao    integer,
    codusuarioalteracao  integer
  );
  CREATE INDEX IF NOT EXISTS ix_contratonota_codcontrato ON tblcontratonota (codcontrato);

  -- 0b) Preserva o que os DROPs abaixo apagariam (a PROD tem contratos reais).
  --     O preco do FIXO ja virou fixacao no passo 09.
  ALTER TABLE tblcontrato ADD COLUMN IF NOT EXISTS barter boolean NOT NULL DEFAULT false;
  UPDATE tblcontrato SET barter = true WHERE tipo = 'BARTER' AND NOT barter;

  ALTER TABLE tblcontrato ALTER COLUMN quantidade DROP NOT NULL;
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblcontrato' AND column_name = 'volumeemaberto') THEN
    EXECUTE 'UPDATE tblcontrato SET quantidade = NULL WHERE volumeemaberto';
  END IF;

  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblcontrato' AND column_name = 'codnaturezaoperacao') THEN
    EXECUTE $nf$
      INSERT INTO tblcontratonota (codcontrato, ordem, codnaturezaoperacao, codpessoanf, observacaonf, criacao, alteracao)
      SELECT c.codcontrato, 1, c.codnaturezaoperacao, c.codpessoanf, c.observacaonf, now(), now()
        FROM tblcontrato c
       WHERE (c.codnaturezaoperacao IS NOT NULL OR c.codpessoanf IS NOT NULL OR c.observacaonf IS NOT NULL)
         AND NOT EXISTS (SELECT 1 FROM tblcontratonota n WHERE n.codcontrato = c.codcontrato)
    $nf$;
  END IF;

  ALTER TABLE tblcontratofixacao ADD COLUMN IF NOT EXISTS isentofethab boolean NOT NULL DEFAULT false;
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblcontrato' AND column_name = 'isentofethab') THEN
    EXECUTE 'UPDATE tblcontratofixacao f SET isentofethab = true
               FROM tblcontrato c
              WHERE c.codcontrato = f.codcontrato AND c.isentofethab AND NOT f.isentofethab';
  END IF;

  -- 1) tblcontrato: tira precificacao, flags redundantes e os campos de NF.
  ALTER TABLE tblcontrato
    DROP COLUMN IF EXISTS preco,
    DROP COLUMN IF EXISTS moeda,
    DROP COLUMN IF EXISTS isentofethab,
    DROP COLUMN IF EXISTS volumeemaberto,
    DROP COLUMN IF EXISTS tipo,
    DROP COLUMN IF EXISTS codnaturezaoperacao,
    DROP COLUMN IF EXISTS codpessoanf,
    DROP COLUMN IF EXISTS observacaonf;

  -- quantidade NULL = volume em aberto.
  ALTER TABLE tblcontrato
    ALTER COLUMN quantidade DROP NOT NULL;

  -- 2) tblcontratofixacao: isencao de FETHAB por fixacao; sem flag de espelho.
  ALTER TABLE tblcontratofixacao
    ADD COLUMN IF NOT EXISTS isentofethab boolean NOT NULL DEFAULT false,
    DROP COLUMN IF EXISTS automatico;

  -- 3) tblcontratopagamento: forma de liquidacao (conta vs barter).
  ALTER TABLE tblcontratopagamento
    ADD COLUMN IF NOT EXISTS forma varchar(10) NOT NULL DEFAULT 'CONTA';
  ALTER TABLE tblcontratopagamento
    DROP CONSTRAINT IF EXISTS chk_contratopagamento_forma;
  ALTER TABLE tblcontratopagamento
    ADD CONSTRAINT chk_contratopagamento_forma
    CHECK (forma IN ('CONTA', 'BARTER'));

  RAISE NOTICE '14 contrato_refatoracao: aplicado';
END
$agro$;

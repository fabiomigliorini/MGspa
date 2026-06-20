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

BEGIN;

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

-- 4) tblcontratonota: plano de emissao de NF por contrato (triangular).
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

COMMIT;

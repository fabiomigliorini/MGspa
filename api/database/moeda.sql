-- =====================================================================
-- Tabela de moedas (cadastro compartilhado; CRUD no app contas).
-- PK = codmoeda bigint com sequence (padrao do projeto; nunca chave texto).
--   moeda = nome      ("Real", "Dolar", "Euro")
--   sigla = simbolo   ("R$", "US$")
--   iso   = ISO 4217  ("BRL", "USD", "EUR") -- unico, usado como FK natural
-- tblcontratofixacao.moeda guarda o ISO e referencia tblmoeda(iso).
-- Reaplicavel.
-- =====================================================================

BEGIN;

-- Remove FK antiga (apontava p/ tblmoeda.moeda quando a PK era texto).
ALTER TABLE tblcontratofixacao
  DROP CONSTRAINT IF EXISTS tblcontratofixacao_moeda_fkey;

DROP TABLE IF EXISTS tblmoeda;

CREATE TABLE tblmoeda (
  codmoeda            bigserial PRIMARY KEY,
  moeda               varchar(60) NOT NULL,         -- nome: Real, Dolar, Euro
  sigla               varchar(5)  NOT NULL,         -- R$, US$
  iso                 varchar(3)  NOT NULL UNIQUE,  -- ISO 4217: BRL, USD, EUR
  inativo             timestamp without time zone,
  criacao             timestamp without time zone,
  alteracao           timestamp without time zone,
  codusuariocriacao   integer,
  codusuarioalteracao integer
);

INSERT INTO tblmoeda (moeda, sigla, iso) VALUES
  ('Real',  'R$',  'BRL'),
  ('Dólar', 'US$', 'USD');

-- FK da fixacao (guarda o ISO 'BRL'/'USD') -> tblmoeda.iso.
ALTER TABLE tblcontratofixacao
  ADD CONSTRAINT tblcontratofixacao_moeda_fkey
  FOREIGN KEY (moeda) REFERENCES tblmoeda(iso);

COMMIT;

-- =====================================================================
-- Tabela de moedas (cadastro compartilhado; CRUD no app contas).
-- PK = codigo ISO 4217 (varchar 3), p/ FK natural e legivel — as colunas
-- moeda já existentes (tblcontratofixacao) guardam 'BRL'/'USD' e seguem válidas.
-- Reaplicavel.
-- =====================================================================

BEGIN;

CREATE TABLE IF NOT EXISTS tblmoeda (
  moeda               varchar(3) PRIMARY KEY,         -- ISO 4217 (BRL, USD, ...)
  descricao           varchar(60) NOT NULL,
  simbolo             varchar(5)  NOT NULL,
  inativo             timestamp without time zone,
  criacao             timestamp without time zone,
  alteracao           timestamp without time zone,
  codusuariocriacao   integer,
  codusuarioalteracao integer
);

INSERT INTO tblmoeda (moeda, descricao, simbolo) VALUES
  ('BRL', 'Real',  'R$'),
  ('USD', 'Dólar', 'US$')
ON CONFLICT (moeda) DO NOTHING;

-- FK da fixação -> moeda (settlement de preço da fixação).
ALTER TABLE tblcontratofixacao
  DROP CONSTRAINT IF EXISTS tblcontratofixacao_moeda_fkey;
ALTER TABLE tblcontratofixacao
  ADD CONSTRAINT tblcontratofixacao_moeda_fkey
  FOREIGN KEY (moeda) REFERENCES tblmoeda(moeda);

COMMIT;

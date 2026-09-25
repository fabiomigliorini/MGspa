-- =====================================================================
-- Vale compras DENTRO do negocio (milestone 3 do plano).
--
-- SOMENTE ESTRUTURA (DDL) — nenhum dado e' tocado. Cria as duas tabelas
-- transacionais novas e a coluna de totalizacao no negocio:
--
--   tblnegociovale             — um vale emitido dentro de um negocio
--   tblnegociovaleprodutobarra — os itens daquele vale
--   tblnegocio.valorvales      — soma da FACE dos vales do negocio
--
-- O QUE O VALE E' (decisao 2 do plano): um BLOCO PROPRIO do negocio. Nao
-- e' produto e NAO e' item de mercadoria — nada disso aqui encosta em
-- tblnegocioprodutobarra. E' o que faz o PDV de quem nao vende vale
-- continuar identico.
--
-- PREFIXO (decisao 11): "tblnegocio..." em tudo que aponta para
-- codnegocio. O catalogo, que nao aponta, e' tblvalemodelo e ja' existe
-- (ver vale_catalogo.sql, milestone 1).
--
-- OS TRES VALORES DO VALE (decisoes 21 e 22):
--   valorprodutos = soma dos itens do vale   (calculado)
--   valoravulso   = valor digitado a mao
--   valorvale     = produtos + avulso  <- a FACE, o credito emitido
--   valortotal    = a FATIA PAGA, depois do rateio do desconto
--                   (milestone 4). Hoje nasce igual a' face.
-- "valorvale" e "valortotal" sao coisas diferentes DE PROPOSITO: a escola
-- recebe a face; o cliente paga a fatia. No exemplo do plano, vale de
-- R$ 200 com R$ 20 de desconto rateado => valorvale 200, valortotal 180,
-- e o credito gerado e' 200 (decisao 5c).
--
-- ITEM DE VALE E' SO' QUANTIDADE x PRECO (decisao 19): desconto e juros
-- param em tblnegociovale e NAO descem ao item. Por isso a tabela de item
-- tem metade das colunas da de mercadoria.
--
-- E nem tudo que a mercadoria tem no cabecalho existe no vale: FRETE,
-- SEGURO e "OUTRAS" nao sao rateados -- nao se cobra frete nem seguro de um
-- vale compras. Eles ficam 100% na mercadoria.
--
-- codvalecompra: o numero do vale ANTIGO (tblvalecompra), preenchido so'
-- na conversao do legado (milestone 9), para o papel que ja' esta' na mao
-- do cliente continuar achavel. Nasce nulo em vale novo.
--
-- codtitulo: o credito (titulo tipo 3) que o fechamento vai emitir no
-- milestone 5. Nasce nulo e e' UNIQUE — e' a rede contra emitir dois
-- creditos para o mesmo vale se o fechamento rodar duas vezes.
--
-- Idempotente (CREATE TABLE IF NOT EXISTS / ADD COLUMN IF NOT EXISTS +
-- guards em pg_constraint). Transacional. Pode rodar de novo a vontade.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

-- ADD COLUMN em tblnegocio pega ACCESS EXCLUSIVE na tabela mais quente do
-- sistema. Com DEFAULT + NOT NULL o Postgres (11+) nao reescreve a tabela,
-- entao o lock e' de milissegundos — mas se alguem estiver com transacao
-- ociosa segurando lock nela, este script ficaria na fila travando o PDV
-- inteiro. Com o lock_timeout ele desiste em 5s e faz rollback de tudo.
-- Nunca remover estes dois SET.
SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '60s';

-- ---------------------------------------------------------------------
-- 1) Cabecalho do vale emitido
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tblnegociovale (
  codnegociovale       bigserial PRIMARY KEY,
  uuid                 uuid NOT NULL DEFAULT gen_random_uuid(),   -- chave do sync offline
  codnegocio           bigint NOT NULL,
  codtitulo            bigint,          -- credito emitido no fechamento (milestone 5)
  codpessoafavorecido  bigint NOT NULL, -- escola; Consumidor (1) = vale ao portador
  aluno                varchar(50),
  turma                varchar(40),
  codvalemodelo        bigint,          -- kit de origem; nulo = vale so' de valor avulso
  valorprodutos        numeric(14,2) NOT NULL DEFAULT 0,  -- soma dos itens
  valoravulso          numeric(14,2) NOT NULL DEFAULT 0,  -- valor digitado
  valorvale            numeric(14,2) NOT NULL DEFAULT 0,  -- FACE = produtos + avulso
  -- So desconto e juros sao rateados entre mercadoria e vale. Frete, seguro
  -- e "outras" NAO existem aqui de proposito: nao se cobra frete nem seguro
  -- de um vale compras -- isso e' da mercadoria que vai ser entregue. Esses
  -- tres ficam 100% na mercadoria.
  valordesconto        numeric(14,2),   -- fatia do cabecalho rateada (milestone 4)
  valorjuros           numeric(14,2),   -- fatia dos juros do parcelamento
  valortotal           numeric(14,2) NOT NULL DEFAULT 0,  -- fatia PAGA, apos rateio
  validade             date,            -- informativa: emissao + 1 ano (decisao 8)
  codvalecompra        bigint,          -- numero do vale antigo (milestone 9)
  observacoes          varchar(200),
  inativo              timestamp(0) without time zone,    -- soft-delete, como o item
  criacao              timestamp(0) without time zone NOT NULL DEFAULT now(),
  codusuariocriacao    bigint,
  alteracao            timestamp(0) without time zone NOT NULL DEFAULT now(),
  codusuarioalteracao  bigint
);

-- ---------------------------------------------------------------------
-- 2) Itens do vale (quantidade x preco, e so' — decisao 19)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tblnegociovaleprodutobarra (
  codnegociovaleprodutobarra  bigserial PRIMARY KEY,
  uuid                 uuid NOT NULL DEFAULT gen_random_uuid(),   -- chave do sync offline
  codnegociovale       bigint NOT NULL,
  codprodutobarra      bigint NOT NULL,
  quantidade           numeric(14,3) NOT NULL,
  valorunitario        numeric(22,10) NOT NULL,
  valorprodutos        numeric(14,2) NOT NULL DEFAULT 0,  -- quantidade x valorunitario
  ordenacao            timestamp(0) without time zone NOT NULL DEFAULT now(),
  inativo              timestamp(0) without time zone,    -- soft-delete
  criacao              timestamp(0) without time zone NOT NULL DEFAULT now(),
  codusuariocriacao    bigint,
  alteracao            timestamp(0) without time zone NOT NULL DEFAULT now(),
  codusuarioalteracao  bigint
);

-- ---------------------------------------------------------------------
-- 2b) Banco que rodou a primeira versao deste arquivo tem as tres colunas
--     que nao fazem sentido no vale. Nao ha' dado a preservar: elas nunca
--     chegaram a producao e em dev so' tinham NULL.
-- ---------------------------------------------------------------------
ALTER TABLE tblnegociovale DROP COLUMN IF EXISTS valorfrete;
ALTER TABLE tblnegociovale DROP COLUMN IF EXISTS valorseguro;
ALTER TABLE tblnegociovale DROP COLUMN IF EXISTS valoroutras;

-- ---------------------------------------------------------------------
-- 3) Indices
--
-- uuid UNIQUE nas duas: o PDV offline faz upsert por uuid (firstOrNew), e
-- e' esse indice que impede um retransmitir duplicar o vale.
-- codtitulo UNIQUE: um credito por vale. NULL nao conflita com NULL no
-- Postgres, entao os vales ainda nao fechados convivem sem problema.
-- ---------------------------------------------------------------------
CREATE UNIQUE INDEX IF NOT EXISTS unq_tblnegociovale_uuid
  ON tblnegociovale(uuid);
CREATE UNIQUE INDEX IF NOT EXISTS unq_tblnegociovale_codtitulo
  ON tblnegociovale(codtitulo);
CREATE INDEX IF NOT EXISTS idx_nv_codnegocio
  ON tblnegociovale(codnegocio);
CREATE INDEX IF NOT EXISTS idx_nv_codpessoafavorecido
  ON tblnegociovale(codpessoafavorecido);
CREATE INDEX IF NOT EXISTS idx_nv_codvalemodelo
  ON tblnegociovale(codvalemodelo);
-- consulta do milestone 9 (repontar os vales antigos convertidos)
CREATE INDEX IF NOT EXISTS idx_nv_codvalecompra
  ON tblnegociovale(codvalecompra);

CREATE UNIQUE INDEX IF NOT EXISTS unq_tblnegociovaleprodutobarra_uuid
  ON tblnegociovaleprodutobarra(uuid);
CREATE INDEX IF NOT EXISTS idx_nvpb_codnegociovale
  ON tblnegociovaleprodutobarra(codnegociovale);
CREATE INDEX IF NOT EXISTS idx_nvpb_codprodutobarra
  ON tblnegociovaleprodutobarra(codprodutobarra);

-- ---------------------------------------------------------------------
-- 4) Chaves estrangeiras
--
-- So' cria quando nao existe: cada FK pega SHARE ROW EXCLUSIVE na tabela
-- referenciada (tblnegocio e tblprodutobarra sao quentes), e o padrao
-- DROP+ADD faria esse lock em toda reexecucao sem necessidade.
-- ---------------------------------------------------------------------
DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_constraint
                 WHERE conname = 'tblnegociovale_codnegocio_fkey'
                   AND conrelid = to_regclass('tblnegociovale')) THEN
    ALTER TABLE tblnegociovale
      ADD CONSTRAINT tblnegociovale_codnegocio_fkey
      FOREIGN KEY (codnegocio) REFERENCES tblnegocio(codnegocio) ON UPDATE CASCADE;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM pg_constraint
                 WHERE conname = 'tblnegociovale_codtitulo_fkey'
                   AND conrelid = to_regclass('tblnegociovale')) THEN
    ALTER TABLE tblnegociovale
      ADD CONSTRAINT tblnegociovale_codtitulo_fkey
      FOREIGN KEY (codtitulo) REFERENCES tbltitulo(codtitulo) ON UPDATE CASCADE;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM pg_constraint
                 WHERE conname = 'tblnegociovale_codpessoafavorecido_fkey'
                   AND conrelid = to_regclass('tblnegociovale')) THEN
    ALTER TABLE tblnegociovale
      ADD CONSTRAINT tblnegociovale_codpessoafavorecido_fkey
      FOREIGN KEY (codpessoafavorecido) REFERENCES tblpessoa(codpessoa) ON UPDATE CASCADE;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM pg_constraint
                 WHERE conname = 'tblnegociovale_codvalemodelo_fkey'
                   AND conrelid = to_regclass('tblnegociovale')) THEN
    ALTER TABLE tblnegociovale
      ADD CONSTRAINT tblnegociovale_codvalemodelo_fkey
      FOREIGN KEY (codvalemodelo) REFERENCES tblvalemodelo(codvalemodelo) ON UPDATE CASCADE;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM pg_constraint
                 WHERE conname = 'tblnegociovale_codusuariocriacao_fkey'
                   AND conrelid = to_regclass('tblnegociovale')) THEN
    ALTER TABLE tblnegociovale
      ADD CONSTRAINT tblnegociovale_codusuariocriacao_fkey
      FOREIGN KEY (codusuariocriacao) REFERENCES tblusuario(codusuario) ON UPDATE CASCADE;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM pg_constraint
                 WHERE conname = 'tblnegociovale_codusuarioalteracao_fkey'
                   AND conrelid = to_regclass('tblnegociovale')) THEN
    ALTER TABLE tblnegociovale
      ADD CONSTRAINT tblnegociovale_codusuarioalteracao_fkey
      FOREIGN KEY (codusuarioalteracao) REFERENCES tblusuario(codusuario) ON UPDATE CASCADE;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM pg_constraint
                 WHERE conname = 'tblnegociovaleprodutobarra_codnegociovale_fkey'
                   AND conrelid = to_regclass('tblnegociovaleprodutobarra')) THEN
    ALTER TABLE tblnegociovaleprodutobarra
      ADD CONSTRAINT tblnegociovaleprodutobarra_codnegociovale_fkey
      FOREIGN KEY (codnegociovale) REFERENCES tblnegociovale(codnegociovale) ON UPDATE CASCADE;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM pg_constraint
                 WHERE conname = 'tblnegociovaleprodutobarra_codprodutobarra_fkey'
                   AND conrelid = to_regclass('tblnegociovaleprodutobarra')) THEN
    ALTER TABLE tblnegociovaleprodutobarra
      ADD CONSTRAINT tblnegociovaleprodutobarra_codprodutobarra_fkey
      FOREIGN KEY (codprodutobarra) REFERENCES tblprodutobarra(codprodutobarra) ON UPDATE CASCADE;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM pg_constraint
                 WHERE conname = 'tblnegociovaleprodutobarra_codusuariocriacao_fkey'
                   AND conrelid = to_regclass('tblnegociovaleprodutobarra')) THEN
    ALTER TABLE tblnegociovaleprodutobarra
      ADD CONSTRAINT tblnegociovaleprodutobarra_codusuariocriacao_fkey
      FOREIGN KEY (codusuariocriacao) REFERENCES tblusuario(codusuario) ON UPDATE CASCADE;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM pg_constraint
                 WHERE conname = 'tblnegociovaleprodutobarra_codusuarioalteracao_fkey'
                   AND conrelid = to_regclass('tblnegociovaleprodutobarra')) THEN
    ALTER TABLE tblnegociovaleprodutobarra
      ADD CONSTRAINT tblnegociovaleprodutobarra_codusuarioalteracao_fkey
      FOREIGN KEY (codusuarioalteracao) REFERENCES tblusuario(codusuario) ON UPDATE CASCADE;
  END IF;
END $$;

-- ---------------------------------------------------------------------
-- 5) Totalizador no negocio
--
-- valorvales e' BRUTO, simetrico ao valorprodutos (decisao 20):
--
--   valortotal = valorprodutos + valorvales - valordesconto + valorfrete
--                + valorseguro + valoroutras + valorjuros
--
-- Negocio sem vale fica com 0 e a conta nao muda em nada — e' o que
-- mantem toda venda de mercadoria identica ao que e' hoje.
--
-- NOT NULL DEFAULT 0 igual ao valorprodutos que ja' existe ao lado. Em
-- PG 11+ isso e' so' metadado: nao reescreve os milhoes de linhas.
-- ---------------------------------------------------------------------
ALTER TABLE tblnegocio
  ADD COLUMN IF NOT EXISTS valorvales numeric(14,2) NOT NULL DEFAULT 0;

COMMIT;

-- ---------------------------------------------------------------------
-- Verificacao (fora da transacao)
-- ---------------------------------------------------------------------
\echo '== colunas de tblnegociovale =='
SELECT column_name, data_type, is_nullable
FROM information_schema.columns
WHERE table_name = 'tblnegociovale' ORDER BY ordinal_position;
\echo '== colunas de tblnegociovaleprodutobarra =='
SELECT column_name, data_type, is_nullable
FROM information_schema.columns
WHERE table_name = 'tblnegociovaleprodutobarra' ORDER BY ordinal_position;
\echo '== tblnegocio.valorvales (esperado: numeric, NOT NULL, default 0) =='
SELECT column_name, data_type, is_nullable, column_default
FROM information_schema.columns
WHERE table_name = 'tblnegocio' AND column_name = 'valorvales';
\echo '== negocios com valorvales diferente de zero (esperado 0 antes do 1o vale) =='
SELECT count(*) AS negocios_com_vale FROM tblnegocio WHERE valorvales <> 0;

-- =====================================================================
-- Modulo Pessoa (Mg\Pessoa) — Cartao da pessoa (Beneficio ou Corporativo)
--
-- SOMENTE ESTRUTURA (DDL) — o unico dado que toca e' o backfill do titular
-- (codcolaborador -> codpessoa) dos cartoes que ja' existiam.
--
-- O QUE FAZ:
--   1) Renomeia tblcolaboradorcartao -> tblpessoacartao (e a PK
--      codcolaboradorcartao -> codpessoacartao, e a sequence).
--   2) Troca o titular: o cartao deixa de pertencer a um VINCULO
--      (codcolaborador) e passa a pertencer a uma PESSOA (codpessoa). Isso
--      e' o que permite cartao em nome de FILIAL — toda filial e' uma pessoa
--      cadastrada (tblfilial.codpessoa). Quem valida que a pessoa e'
--      colaborador ou filial e' a aplicacao (PessoaCartaoController).
--   3) Cria a coluna `tipo`: 'B' = Beneficio (Bee), 'C' = Corporativo.
--      Os cartoes que ja' existiam viram 'B', que e' o correto.
--
-- SUBSTITUI o antigo rh_cartao_beneficio.sql (removido no mesmo commit —
-- rodar aquele script hoje recriaria a tabela na forma velha).
--
-- ATENCAO ao nome da PK: `codpessoacartao` JA' EXISTE como coluna em
-- tblliquidacaotitulo, onde significa outra coisa (e' um codpessoa — o
-- titular do cartao de credito usado na liquidacao) e NAO e' FK desta
-- tabela. Nao inferir relacao entre as duas.
--
-- O numero do cartao continua CRIPTOGRAFADO (cast encrypted do Laravel) —
-- nunca legivel no banco; a API devolve so' a mascara "1234 **** **** 5678".
-- NAO ha' coluna de CVC (vedado pelo PCI DSS). Validade em duas colunas
-- MM/AA. Inativo timestamp (NULL = ativo); nunca se deleta, so' inativa.
--
-- Idempotente (guards de information_schema + IF NOT EXISTS). Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

-- ---------------------------------------------------------------------
-- 1) Rename da tabela, da PK e da sequence (base que ja' tem a tabela velha)
-- ---------------------------------------------------------------------
DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.tables
             WHERE table_name = 'tblcolaboradorcartao')
     AND NOT EXISTS (SELECT 1 FROM information_schema.tables
             WHERE table_name = 'tblpessoacartao') THEN
    ALTER TABLE tblcolaboradorcartao RENAME TO tblpessoacartao;
  END IF;

  IF EXISTS (SELECT 1 FROM information_schema.columns
             WHERE table_name = 'tblpessoacartao' AND column_name = 'codcolaboradorcartao')
     AND NOT EXISTS (SELECT 1 FROM information_schema.columns
             WHERE table_name = 'tblpessoacartao' AND column_name = 'codpessoacartao') THEN
    ALTER TABLE tblpessoacartao RENAME COLUMN codcolaboradorcartao TO codpessoacartao;
  END IF;

  -- ALTER TABLE ... RENAME nao renomeia a sequence do bigserial.
  IF EXISTS (SELECT 1 FROM information_schema.sequences
             WHERE sequence_name = 'tblcolaboradorcartao_codcolaboradorcartao_seq') THEN
    ALTER SEQUENCE tblcolaboradorcartao_codcolaboradorcartao_seq
      RENAME TO tblpessoacartao_codpessoacartao_seq;
  END IF;
END $$;

-- ---------------------------------------------------------------------
-- 2) Tabela na forma nova (base zerada — nao faz nada se ja' existe)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tblpessoacartao (
  codpessoacartao      bigserial PRIMARY KEY,
  codpessoa            bigint NOT NULL,
  tipo                 char(1) NOT NULL DEFAULT 'B',   -- B = Beneficio, C = Corporativo
  numero               text NOT NULL,   -- criptografado (cast encrypted) — nunca legivel no banco
  validademes          smallint NOT NULL,
  validadeano          smallint NOT NULL,
  email                varchar(255),
  observacao           text,
  inativo              timestamp(0) without time zone,
  criacao              timestamp(0) without time zone NOT NULL DEFAULT now(),
  codusuariocriacao    bigint,
  alteracao            timestamp(0) without time zone NOT NULL DEFAULT now(),
  codusuarioalteracao  bigint
);

-- ---------------------------------------------------------------------
-- 3) Titular: codcolaborador -> codpessoa
-- ---------------------------------------------------------------------
ALTER TABLE tblpessoacartao ADD COLUMN IF NOT EXISTS codpessoa bigint;

-- Backfill: pega a pessoa do vinculo a que o cartao pertencia.
DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns
             WHERE table_name = 'tblpessoacartao' AND column_name = 'codcolaborador') THEN
    UPDATE tblpessoacartao pc
       SET codpessoa = c.codpessoa
      FROM tblcolaborador c
     WHERE c.codcolaborador = pc.codcolaborador
       AND pc.codpessoa IS NULL;
  END IF;
END $$;

ALTER TABLE tblpessoacartao ALTER COLUMN codpessoa SET NOT NULL;

ALTER TABLE tblpessoacartao
  DROP CONSTRAINT IF EXISTS tblpessoacartao_codpessoa_fkey;
ALTER TABLE tblpessoacartao
  ADD CONSTRAINT tblpessoacartao_codpessoa_fkey
  FOREIGN KEY (codpessoa) REFERENCES tblpessoa(codpessoa) ON UPDATE CASCADE;

-- O vinculo com o colaborador sai de cena (a FK antiga veio junto no rename).
ALTER TABLE tblpessoacartao
  DROP CONSTRAINT IF EXISTS tblcolaboradorcartao_codcolaborador_fkey;
ALTER TABLE tblpessoacartao DROP COLUMN IF EXISTS codcolaborador;

-- ---------------------------------------------------------------------
-- 4) Tipo do cartao: Beneficio (Bee) ou Corporativo
-- ---------------------------------------------------------------------
ALTER TABLE tblpessoacartao ADD COLUMN IF NOT EXISTS tipo char(1) NOT NULL DEFAULT 'B';

ALTER TABLE tblpessoacartao DROP CONSTRAINT IF EXISTS chk_pc_tipo;
ALTER TABLE tblpessoacartao
  ADD CONSTRAINT chk_pc_tipo CHECK (tipo = ANY (ARRAY['B','C']::bpchar[]));

-- ---------------------------------------------------------------------
-- 5) Indice e CHECKs no prefixo novo (pc = pessoacartao)
-- ---------------------------------------------------------------------
DROP INDEX IF EXISTS idx_cc_colaborador;
CREATE INDEX IF NOT EXISTS idx_pc_pessoa ON tblpessoacartao(codpessoa);

ALTER TABLE tblpessoacartao DROP CONSTRAINT IF EXISTS chk_cc_validademes;
ALTER TABLE tblpessoacartao DROP CONSTRAINT IF EXISTS chk_cc_validadeano;
ALTER TABLE tblpessoacartao DROP CONSTRAINT IF EXISTS chk_pc_validademes;
ALTER TABLE tblpessoacartao DROP CONSTRAINT IF EXISTS chk_pc_validadeano;
ALTER TABLE tblpessoacartao
  ADD CONSTRAINT chk_pc_validademes CHECK (validademes BETWEEN 1 AND 12);
ALTER TABLE tblpessoacartao
  ADD CONSTRAINT chk_pc_validadeano CHECK (validadeano BETWEEN 0 AND 99);

COMMIT;

-- ---------------------------------------------------------------------
-- Verificacao (fora da transacao)
-- ---------------------------------------------------------------------
\echo '== cartoes por tipo =='
SELECT tipo, count(*) AS cartoes FROM tblpessoacartao GROUP BY tipo ORDER BY tipo;
\echo '== colunas =='
SELECT column_name, data_type FROM information_schema.columns
WHERE table_name = 'tblpessoacartao' ORDER BY ordinal_position;
\echo '== titular sem pessoa (tem que voltar zero) =='
SELECT count(*) AS orfaos FROM tblpessoacartao pc
LEFT JOIN tblpessoa p ON p.codpessoa = pc.codpessoa WHERE p.codpessoa IS NULL;

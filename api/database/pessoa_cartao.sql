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

-- A FK nova pega SHARE ROW EXCLUSIVE em tblpessoa (tabela quente): se alguem
-- estiver com transacao ociosa segurando lock nela, este script ficaria na
-- fila SEGURANDO o lock da tabela do cartao e travando a API junto. Com o
-- lock_timeout ele desiste em 5s e faz rollback de tudo — e' so' rodar de novo
-- depois. Nunca remover estes dois SET.
SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '60s';

-- ---------------------------------------------------------------------
-- 0) Backup do estado anterior (so' quando ha' o que migrar)
--
-- Guarda a tabela velha inteira antes de qualquer DDL destrutivo (o
-- DROP COLUMN codcolaborador la' na secao 3 e' irreversivel). Padrao _bkp_*
-- do rh_rubricas.sql. ATENCAO: a copia carrega os numeros de cartao
-- criptografados — DERRUBAR a copia (DROP TABLE _bkp_colaboradorcartao)
-- assim que a migracao for validada em tela.
-- ---------------------------------------------------------------------
DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.tables
             WHERE table_schema = current_schema() AND table_name = 'tblcolaboradorcartao') THEN
    DROP TABLE IF EXISTS _bkp_colaboradorcartao;
    CREATE TABLE _bkp_colaboradorcartao AS SELECT * FROM tblcolaboradorcartao;
  END IF;
END $$;

-- ---------------------------------------------------------------------
-- 1) Rename da tabela, da PK e da sequence (base que ja' tem a tabela velha)
-- ---------------------------------------------------------------------
DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.tables
             WHERE table_schema = current_schema() AND table_name = 'tblcolaboradorcartao')
     AND NOT EXISTS (SELECT 1 FROM information_schema.tables
             WHERE table_schema = current_schema() AND table_name = 'tblpessoacartao') THEN
    ALTER TABLE tblcolaboradorcartao RENAME TO tblpessoacartao;
  END IF;

  IF EXISTS (SELECT 1 FROM information_schema.columns
             WHERE table_schema = current_schema() AND table_name = 'tblpessoacartao'
               AND column_name = 'codcolaboradorcartao')
     AND NOT EXISTS (SELECT 1 FROM information_schema.columns
             WHERE table_schema = current_schema() AND table_name = 'tblpessoacartao'
               AND column_name = 'codpessoacartao') THEN
    ALTER TABLE tblpessoacartao RENAME COLUMN codcolaboradorcartao TO codpessoacartao;
  END IF;

  -- ALTER TABLE ... RENAME nao renomeia a sequence do bigserial.
  IF EXISTS (SELECT 1 FROM information_schema.sequences
             WHERE sequence_schema = current_schema()
               AND sequence_name = 'tblcolaboradorcartao_codcolaboradorcartao_seq') THEN
    ALTER SEQUENCE tblcolaboradorcartao_codcolaboradorcartao_seq
      RENAME TO tblpessoacartao_codpessoacartao_seq;
  END IF;

  -- Nem a constraint da PK (cosmetico, mas confunde quem le o \d depois).
  IF EXISTS (SELECT 1 FROM pg_constraint
             WHERE conname = 'tblcolaboradorcartao_pkey'
               AND conrelid = to_regclass('tblpessoacartao')) THEN
    ALTER TABLE tblpessoacartao RENAME CONSTRAINT tblcolaboradorcartao_pkey TO tblpessoacartao_pkey;
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

-- Bases antigas podem ter rodado uma versao do script anterior sem a coluna
-- email (ela veio depois, por ALTER) — garante que existe nos dois caminhos.
ALTER TABLE tblpessoacartao ADD COLUMN IF NOT EXISTS email varchar(255);

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

-- Se algum cartao ficou sem titular resolvido (vinculo apagado, ou
-- tblcolaborador.codpessoa nulo), aborta com mensagem clara em vez de estourar
-- uma violacao de NOT NULL cifrada. A transacao inteira volta atras: nada e'
-- aplicado, e' so' resolver o(s) registro(s) e rodar de novo.
DO $$
DECLARE
  v_pendentes bigint;
BEGIN
  SELECT count(*) INTO v_pendentes FROM tblpessoacartao WHERE codpessoa IS NULL;
  IF v_pendentes > 0 THEN
    RAISE EXCEPTION
      'Migracao abortada: % cartao(oes) sem codpessoa resolvido. Rode: SELECT codpessoacartao, codcolaborador FROM tblpessoacartao WHERE codpessoa IS NULL;',
      v_pendentes;
  END IF;
END $$;

ALTER TABLE tblpessoacartao ALTER COLUMN codpessoa SET NOT NULL;

-- Criar a FK exige SHARE ROW EXCLUSIVE em tblpessoa, que bloqueia escrita
-- naquela tabela quente. Por isso ela so' e' criada quando NAO existe: numa
-- reexecucao o script nem encosta na tblpessoa (padrao DROP+ADD faria o lock
-- toda vez, sem necessidade).
DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_constraint
                 WHERE conname = 'tblpessoacartao_codpessoa_fkey'
                   AND conrelid = to_regclass('tblpessoacartao')) THEN
    ALTER TABLE tblpessoacartao
      ADD CONSTRAINT tblpessoacartao_codpessoa_fkey
      FOREIGN KEY (codpessoa) REFERENCES tblpessoa(codpessoa) ON UPDATE CASCADE;
  END IF;
END $$;

-- Antes de dropar o vinculo, guarda o mapa cartao -> colaborador de origem.
-- Isso importa para quem tem MAIS DE UM vinculo (ex.: cartao pela Migliorini e
-- outro pela FDF): depois da migracao os dois cartoes ficam na mesma pessoa e
-- a tela mostra so' a empresa do vinculo ativo — este mapa e' o unico registro
-- de qual cartao veio de qual empresa. Sem o numero do cartao, entao e' seguro
-- manter para sempre (diferente do _bkp_colaboradorcartao, que tem o numero).
DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns
             WHERE table_schema = current_schema() AND table_name = 'tblpessoacartao'
               AND column_name = 'codcolaborador') THEN
    DROP TABLE IF EXISTS _bkp_cartao_vinculo;
    CREATE TABLE _bkp_cartao_vinculo AS
      SELECT codpessoacartao, codcolaborador, codpessoa FROM tblpessoacartao;
  END IF;
END $$;

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
\echo '== conferir contra o backup (tem que bater) =='
DO $$
DECLARE
  v_antes bigint;
  v_depois bigint;
BEGIN
  SELECT count(*) INTO v_depois FROM tblpessoacartao;
  IF to_regclass('_bkp_colaboradorcartao') IS NULL THEN
    RAISE NOTICE 'base sem tabela anterior (nada a migrar). cartoes = %', v_depois;
  ELSE
    EXECUTE 'SELECT count(*) FROM _bkp_colaboradorcartao' INTO v_antes;
    -- O backup e' o retrato de antes da migracao. Perder linha e' o que importa;
    -- ter a mais e' normal (cartao cadastrado depois, se o script for reexecutado).
    RAISE NOTICE 'cartoes antes = %, depois = % %', v_antes, v_depois,
      CASE
        WHEN v_depois < v_antes THEN '(FALTOU REGISTRO — CONFERIR!)'
        WHEN v_depois > v_antes THEN '(ha cartoes novos desde o backup — ok)'
        ELSE '(OK)'
      END;
    RAISE NOTICE 'Depois de validar na tela: DROP TABLE _bkp_colaboradorcartao;';
    RAISE NOTICE '(a copia guarda os numeros de cartao criptografados — nao deixar pra tras)';
  END IF;
END $$;

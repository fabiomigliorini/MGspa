-- =====================================================================
-- Modulo RH (Mg\Colaborador) — Cartao Beneficio (cartao Bee do colaborador)
--
-- SOMENTE ESTRUTURA (DDL) — nao toca em dado nenhum.
--
-- O QUE CRIA:
--   tblcolaboradorcartao = cartao-beneficio (Bee) de um colaborador. Vinculo
--   por codcolaborador (o cartao pertence a' empresa do vinculo). O numero do
--   cartao e' guardado CRIPTOGRAFADO (cast encrypted do Laravel) — nunca
--   legivel no banco; a API devolve so' os 4 ultimos digitos. NAO ha' coluna
--   de CVC (vedado pelo PCI DSS e sem uso no fluxo de recarga, que e' por CPF).
--   Validade em duas colunas MM/AA (o plastico traz MM/AA, sem dia).
--   Inativo timestamp (NULL = ativo); nunca se deleta um cartao, so' inativa.
--
-- Aditivo e idempotente (IF NOT EXISTS). Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

CREATE TABLE IF NOT EXISTS tblcolaboradorcartao (
  codcolaboradorcartao bigserial PRIMARY KEY,
  codcolaborador       bigint NOT NULL,
  numero               text NOT NULL,   -- criptografado (cast encrypted) — nunca legivel no banco
  validademes          smallint NOT NULL,
  validadeano          smallint NOT NULL,
  observacao           text,
  inativo              timestamp(0) without time zone,
  criacao              timestamp(0) without time zone NOT NULL DEFAULT now(),
  codusuariocriacao    bigint,
  alteracao            timestamp(0) without time zone NOT NULL DEFAULT now(),
  codusuarioalteracao  bigint,
  CONSTRAINT tblcolaboradorcartao_codcolaborador_fkey
    FOREIGN KEY (codcolaborador)
    REFERENCES tblcolaborador(codcolaborador) ON UPDATE CASCADE,
  CONSTRAINT chk_cc_validademes CHECK (validademes BETWEEN 1 AND 12),
  CONSTRAINT chk_cc_validadeano CHECK (validadeano BETWEEN 0 AND 99)
);

CREATE INDEX IF NOT EXISTS idx_cc_colaborador
  ON tblcolaboradorcartao(codcolaborador);

-- Coluna email (idempotente p/ bases onde a tabela ja' existe). Email do
-- colaborador para a ativacao do cartao Bee — o pessoa.email costuma ser o
-- generico nfe@; aqui fica o email pessoal do beneficiario.
ALTER TABLE tblcolaboradorcartao ADD COLUMN IF NOT EXISTS email varchar(255);

COMMIT;

-- ---------------------------------------------------------------------
-- Verificacao (fora da transacao)
-- ---------------------------------------------------------------------
\echo '== tabela criada =='
SELECT count(*) AS cartoes FROM tblcolaboradorcartao;
\echo '== colunas =='
SELECT column_name, data_type FROM information_schema.columns
WHERE table_name = 'tblcolaboradorcartao' ORDER BY ordinal_position;

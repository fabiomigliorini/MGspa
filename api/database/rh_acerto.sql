-- =====================================================================
-- Modulo RH (Mg\Rh) — Refatoracao do Acerto (Encontro de Contas)
--
-- SOMENTE ESTRUTURA (DDL) — nao toca em dado nenhum. As liquidacoes/titulos
-- antigos ficam como estao; uma migracao a' parte, depois, traz os acertos
-- existentes pra tabela nova.
--
-- O QUE CRIA:
--   1. tblperiodocolaboradoracerto = EVENTO de acerto (data, forma B/D/F,
--      valor). Substitui o par titulo-RH + liquidacao daqui pra frente. Varios
--      eventos por colaborador; NUNCA se deleta um evento — so' inativa
--      (coluna inativo) e cria-se outro.
--   2. tblmovimentotitulo ganha codperiodocolaboradoracerto (FK anulavel):
--      a baixa do vale pendura no evento, sem liquidacao. A trigger
--      fntblmovimentotituloaiauad baixa o saldo do titulo normalmente (soma
--      TODOS os movimentos; o recalculo de tblliquidacaotitulo so' considera
--      tipo 600, logo os 601 do RH nao mexem em liquidacao nenhuma).
--
-- Aditivo e idempotente (IF NOT EXISTS / DROP+ADD da constraint). Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

-- ---------------------------------------------------------------------
-- 1) Tabela de eventos de acerto
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tblperiodocolaboradoracerto (
  codperiodocolaboradoracerto bigserial PRIMARY KEY,
  codperiodocolaborador       bigint NOT NULL,
  data                        date NOT NULL DEFAULT CURRENT_DATE,
  forma                       char(1) NOT NULL,
  rubricas                    numeric(14,2) NOT NULL DEFAULT 0,  -- quanto do benefício (rubricas) foi considerado
  creditos                    numeric(14,2) NOT NULL DEFAULT 0,  -- total de títulos a crédito pagos
  debitos                     numeric(14,2) NOT NULL DEFAULT 0,  -- total de títulos a débito descontados
  saldo                       numeric(14,2) NOT NULL DEFAULT 0,  -- rubricas + creditos - debitos (com sinal; magnitude = ABS)
  observacao                  text,
  inativo                     timestamp(0) without time zone,
  criacao                     timestamp(0) without time zone NOT NULL DEFAULT now(),
  codusuariocriacao           bigint,
  alteracao                   timestamp(0) without time zone NOT NULL DEFAULT now(),
  codusuarioalteracao         bigint,
  CONSTRAINT tblperiodocolaboradoracerto_codperiodocolaborador_fkey
    FOREIGN KEY (codperiodocolaborador)
    REFERENCES tblperiodocolaborador(codperiodocolaborador) ON DELETE CASCADE,
  CONSTRAINT chk_pca_forma CHECK (forma = ANY (ARRAY['B','D','F']::bpchar[]))
);
CREATE INDEX IF NOT EXISTS idx_pca_periodocolaborador
  ON tblperiodocolaboradoracerto(codperiodocolaborador);

-- Colunas de decomposição (idempotente p/ bases onde a tabela já existe)
ALTER TABLE tblperiodocolaboradoracerto ADD COLUMN IF NOT EXISTS rubricas numeric(14,2) NOT NULL DEFAULT 0;
ALTER TABLE tblperiodocolaboradoracerto ADD COLUMN IF NOT EXISTS creditos numeric(14,2) NOT NULL DEFAULT 0;
ALTER TABLE tblperiodocolaboradoracerto ADD COLUMN IF NOT EXISTS debitos  numeric(14,2) NOT NULL DEFAULT 0;
ALTER TABLE tblperiodocolaboradoracerto ADD COLUMN IF NOT EXISTS saldo    numeric(14,2) NOT NULL DEFAULT 0;
-- `valor` era redundante (= ABS(saldo)); removido.
ALTER TABLE tblperiodocolaboradoracerto DROP COLUMN IF EXISTS valor;

-- ---------------------------------------------------------------------
-- 2) FK no movimento de titulo (baixa do vale pendurada no evento)
-- ---------------------------------------------------------------------
ALTER TABLE tblmovimentotitulo
  ADD COLUMN IF NOT EXISTS codperiodocolaboradoracerto bigint;

ALTER TABLE tblmovimentotitulo
  DROP CONSTRAINT IF EXISTS tblmovimentotitulo_codperiodocolaboradoracerto_fkey;
ALTER TABLE tblmovimentotitulo
  ADD CONSTRAINT tblmovimentotitulo_codperiodocolaboradoracerto_fkey
  FOREIGN KEY (codperiodocolaboradoracerto)
  REFERENCES tblperiodocolaboradoracerto(codperiodocolaboradoracerto) ON UPDATE CASCADE;

CREATE INDEX IF NOT EXISTS idx_movtitulo_pca
  ON tblmovimentotitulo(codperiodocolaboradoracerto);

COMMIT;

-- ---------------------------------------------------------------------
-- Verificacao (fora da transacao)
-- ---------------------------------------------------------------------
\echo '== tabela criada =='
SELECT count(*) AS eventos FROM tblperiodocolaboradoracerto;
\echo '== coluna FK no movimento =='
SELECT column_name FROM information_schema.columns
WHERE table_name = 'tblmovimentotitulo' AND column_name = 'codperiodocolaboradoracerto';

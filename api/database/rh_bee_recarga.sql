-- =====================================================================
-- Modulo RH (Mg\Rh) — Recarga do cartao-beneficio (Bee)
--
-- SOMENTE ESTRUTURA (DDL) — nao toca em dado nenhum.
--
-- CONTEXTO: as duas tabelas abaixo foram criadas a' mao no banco de dev antes
-- deste script existir. Ele e' aditivo e idempotente: em base que ja' tem as
-- tabelas, so' aplica os ajustes das secoes 3 e 4; em base zerada, cria tudo.
--
-- O QUE MODELA:
--   1. tblbeerecarga = LOTE de recarga de UMA FILIAL num periodo. Nasce quando
--      o RH exporta a planilha (por isso `exportacao` e' NOT NULL: nao existe
--      lote nao-exportado). Carrega o titulo de adiantamento a' Beevale que a
--      geracao cria — hoje esse titulo e' digitado a' mao pelo financeiro.
--
--      COMO LER O codfilial: e' a filial do VINCULO dos colaboradores do lote
--      (tblcolaborador.codfilial, onde eles sao registrados) — e tambem onde o
--      titulo e' lancado. Nao e' a filial da unidade de negocio onde a pessoa
--      trabalha: as duas divergem (quem e' registrado no Deposito pode trabalhar
--      no Botanico).
--
--      Filial pertence a UMA empresa, entao nenhum lote mistura CNPJ por
--      construcao — e' isso que permite um titulo por lote sem agrupar nada.
--      Nao ha' coluna codempresa de proposito: ela e' derivada
--      (tblfilial.codempresa), e guardar as duas so' criaria chance de divergir.
--   2. tblbeerecargaperiodocolaborador = os itens do lote (quem entrou e com
--      quanto). E' o registro IMUTAVEL do que foi enviado a' operadora: a
--      planilha de um lote antigo le' daqui, nunca recalcula o acerto.
--
-- COMO A TELA EVITA RECARGA DUPLICADA (vive na aplicacao, nao no banco): a
-- previa nao pergunta "quem ainda nao entrou em lote"; ela calcula
--     saldo = extrato (acertos forma 'B' do periodo) - soma dos itens vivos
-- e so' entra em lote novo quem tem saldo > 0. Isso cobre de graca a recarga
-- parcial e o acerto que cresceu depois de uma recarga. Nao da' pra fazer com
-- constraint unica: a condicao "vivo" mora na tabela pai (tblbeerecarga.inativo)
-- e nao na do item. O BeeRecargaService garante dentro da transacao, com um
-- FOR UPDATE em tblperiodo. O indice da secao 4 serve a essa soma — NAO e'
-- unico de proposito.
--
-- Inativar um lote nao apaga os itens (eles sao o historico do que foi
-- enviado); eles apenas param de contar no saldo, porque a soma filtra por
-- b.inativo IS NULL — e o colaborador volta a ter saldo pendente.
--
-- Idempotente (IF NOT EXISTS + guards de information_schema). Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

-- As FKs novas pegam SHARE ROW EXCLUSIVE em tblperiodocolaborador e tbltitulo
-- (tabelas quentes): se alguem estiver com transacao ociosa segurando lock
-- nelas, este script ficaria na fila SEGURANDO o lock das tabelas da recarga e
-- travando a API junto. Com o lock_timeout ele desiste em 5s e faz rollback de
-- tudo — e' so' rodar de novo depois. Nunca remover estes dois SET.
SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '60s';

-- ---------------------------------------------------------------------
-- 1) Lote de recarga (uma filial, um periodo)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tblbeerecarga (
  codbeerecarga       bigserial PRIMARY KEY,
  codperiodo          bigint NOT NULL,
  codfilial           bigint NOT NULL,                   -- filial do vinculo; tambem a do titulo
  codtitulo           bigint NOT NULL,                   -- adiantamento a' Beevale criado na geracao
  valor               numeric(14,2) NOT NULL,            -- soma dos itens
  dia                 date NOT NULL,                     -- data em que o saldo tem que estar no cartao
  status              varchar(10) NOT NULL DEFAULT 'PEND',
  exportacao          timestamp(0) without time zone NOT NULL,
  inativo             timestamp(0) without time zone,
  criacao             timestamp(0) without time zone DEFAULT now(),
  codusuariocriacao   bigint,
  alteracao           timestamp(0) without time zone DEFAULT now(),
  codusuarioalteracao bigint,
  CONSTRAINT fk_tblbeerecarga_tblperiodo
    FOREIGN KEY (codperiodo) REFERENCES tblperiodo(codperiodo) ON UPDATE CASCADE,
  CONSTRAINT fk_tblbeerecarga_tblfilial
    FOREIGN KEY (codfilial) REFERENCES tblfilial(codfilial) ON UPDATE CASCADE,
  CONSTRAINT fk_tblbeerecarga_tbltitulo
    FOREIGN KEY (codtitulo) REFERENCES tbltitulo(codtitulo) ON UPDATE CASCADE,
  CONSTRAINT fk_tblbeerecarga_tblusuario
    FOREIGN KEY (codusuariocriacao) REFERENCES tblusuario(codusuario) ON UPDATE CASCADE,
  CONSTRAINT fk_tblbeerecarga_tblusuario_0
    FOREIGN KEY (codusuarioalteracao) REFERENCES tblusuario(codusuario) ON UPDATE CASCADE,
  CONSTRAINT cns_tblbeerecarga CHECK (status::text = ANY (ARRAY['PEND','OK']::text[]))
);

-- ---------------------------------------------------------------------
-- 2) Itens do lote — o que foi efetivamente enviado a' operadora
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tblbeerecargaperiodocolaborador (
  codbeerecargaperiodocolaborador bigserial PRIMARY KEY,
  codbeerecarga                   bigint NOT NULL,
  codperiodocolaborador           bigint NOT NULL,
  valor                           numeric(14,2) NOT NULL,
  criacao                         timestamp(0) without time zone DEFAULT now(),
  codusuariocriacao               bigint,
  alteracao                       timestamp(0) without time zone DEFAULT now(),
  codusuarioalteracao             bigint,
  CONSTRAINT fk_tblbeerecargaperiodocolaborador_tblbeerecarga
    FOREIGN KEY (codbeerecarga) REFERENCES tblbeerecarga(codbeerecarga) ON UPDATE CASCADE,
  CONSTRAINT fk_tblbeerecargaperiodocolaborador_tblperiodocolaborador
    FOREIGN KEY (codperiodocolaborador)
    REFERENCES tblperiodocolaborador(codperiodocolaborador) ON UPDATE CASCADE,
  CONSTRAINT fk_tblbeerecargaperiodocolaborador_tblusuario
    FOREIGN KEY (codusuariocriacao) REFERENCES tblusuario(codusuario) ON UPDATE CASCADE,
  CONSTRAINT fk_tblbeerecargaperiodocolaborador_tblusuario_0
    FOREIGN KEY (codusuarioalteracao) REFERENCES tblusuario(codusuario) ON UPDATE CASCADE
);

-- ---------------------------------------------------------------------
-- 3) Ajustes na estrutura que ja' foi criada a' mao
--
-- valor do item nasceu anulavel sem motivo — todo item tem valor, e a soma do
-- lote depende disso. As tabelas estao vazias, entao o SET NOT NULL nao pode
-- falhar; o guard esta' aqui pra base que ja' tenha dado.
-- ---------------------------------------------------------------------
DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns
             WHERE table_schema = current_schema()
               AND table_name = 'tblbeerecargaperiodocolaborador'
               AND column_name = 'valor'
               AND is_nullable = 'YES')
     AND NOT EXISTS (SELECT 1 FROM tblbeerecargaperiodocolaborador WHERE valor IS NULL) THEN
    ALTER TABLE tblbeerecargaperiodocolaborador ALTER COLUMN valor SET NOT NULL;
  END IF;
END $$;

-- Justificativa do lote. Nasceu com a recarga avulsa: o lote do mes se explica
-- sozinho (e' a empresa inteira), mas um lote de uma pessoa so' com valor
-- quebrado nao — sem isto o historico nao diz POR QUE aquilo foi pago. Vai
-- junto na observacao do titulo de adiantamento.
ALTER TABLE tblbeerecarga ADD COLUMN IF NOT EXISTS observacao text;

-- Auditoria: as duas nasceram sem DEFAULT now() (tblpessoacartao tem).
ALTER TABLE tblbeerecarga                   ALTER COLUMN criacao   SET DEFAULT now();
ALTER TABLE tblbeerecarga                   ALTER COLUMN alteracao SET DEFAULT now();
ALTER TABLE tblbeerecargaperiodocolaborador ALTER COLUMN criacao   SET DEFAULT now();
ALTER TABLE tblbeerecargaperiodocolaborador ALTER COLUMN alteracao SET DEFAULT now();

-- ---------------------------------------------------------------------
-- 4) Indices
--
-- O primeiro serve a' soma do LATERAL da previa (a consulta mais quente do
-- modulo: roda a cada abertura de tab). NAO e' unico — ver o cabecalho.
-- O segundo e' o filtro de toda a tela (historico de um periodo/filial).
-- ---------------------------------------------------------------------
CREATE INDEX IF NOT EXISTS idx_brpc_periodocolaborador
  ON tblbeerecargaperiodocolaborador(codperiodocolaborador);
CREATE INDEX IF NOT EXISTS idx_brpc_beerecarga
  ON tblbeerecargaperiodocolaborador(codbeerecarga);
CREATE INDEX IF NOT EXISTS idx_beerecarga_periodo_filial
  ON tblbeerecarga(codperiodo, codfilial);

COMMIT;

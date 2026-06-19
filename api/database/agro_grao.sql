-- =====================================================================
-- Modulo Grao (Mg\Grao) — Carga unificada (recebimento + expedicao +
-- transferencia) + extrato/razao (tblmovimentograo) + unidade armazenadora.
--
-- Substitui o modelo antigo (carga-colheita + embarque). UMA entidade Carga
-- com N origens e N destinos (pontos); o sistema GERA o extrato a partir dos
-- pontos (idempotente). Saldo de silo/contrato/talhao = SUM(liquido) no extrato.
--
-- Nomenclatura de pesagem (toda tabela de grao):
--   pbt   = peso bruto total (caminhao + carga)
--   tara  = caminhao vazio
--   bruto = pbt - tara            (so o grao)
--   desconto = perdas de classificacao (umidade+impureza+avariados)
--   liquido  = bruto - desconto   (peso seco negociavel)
--
-- Schema mgsis. Convencoes tbl<nome>/cod<nome>, auditoria MgModel, inativo.
-- Idempotente: roda em dev e prod (DROP IF EXISTS + recria). Agro nao tem
-- dados reais em producao.
-- =====================================================================

BEGIN;

-- Aposenta o modelo antigo (carga-colheita + embarque).
DROP TABLE IF EXISTS tblcargacolheitaplantio CASCADE;
DROP TABLE IF EXISTS tblcargacolheita CASCADE;
DROP TABLE IF EXISTS tblembarqueorigem CASCADE;
DROP TABLE IF EXISTS tblembarquecontrato CASCADE;
DROP TABLE IF EXISTS tblembarque CASCADE;

-- ===================== Contrato: direcao + volume em aberto ===========
-- operacao = direcao comercial (VENDA = padrao; COMPRA = compra de vizinho).
ALTER TABLE tblcontrato ADD COLUMN IF NOT EXISTS operacao varchar(10) NOT NULL DEFAULT 'VENDA';

-- "sem limite" -> "volume em aberto" (volume total nao definido: rapa-silo,
-- armazenagem em silo de terceiro). Renomeia se a coluna antiga existir.
DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns
             WHERE table_name = 'tblcontrato' AND column_name = 'semlimite')
     AND NOT EXISTS (SELECT 1 FROM information_schema.columns
             WHERE table_name = 'tblcontrato' AND column_name = 'volumeemaberto') THEN
    ALTER TABLE tblcontrato RENAME COLUMN semlimite TO volumeemaberto;
  END IF;
END $$;
ALTER TABLE tblcontrato ADD COLUMN IF NOT EXISTS volumeemaberto boolean NOT NULL DEFAULT false;

-- ===================== Unidade armazenadora ===========================
DROP TABLE IF EXISTS tblmovimentograo CASCADE;
DROP TABLE IF EXISTS tblcargaponto CASCADE;
DROP TABLE IF EXISTS tblcarga CASCADE;
DROP TABLE IF EXISTS tblunidadearmazenadora CASCADE;

-- Silo proprio, armazem de terceiro ou silo bag. Nao tem filial (silo pode ser
-- de terceiro) — por isso NAO reusa tblestoquelocal.
CREATE TABLE tblunidadearmazenadora (
  codunidadearmazenadora serial PRIMARY KEY,
  unidadearmazenadora    varchar(60) NOT NULL,
  tipo                   varchar(10) NOT NULL,                 -- PROPRIO | TERCEIRO | SILOBAG
  codpessoa              integer REFERENCES tblpessoa(codpessoa),  -- dono (terceiro)
  capacidadesacas        numeric(14,3),
  observacao             text,
  inativo                timestamp,
  criacao                timestamp,
  alteracao              timestamp,
  codusuariocriacao      integer,
  codusuarioalteracao    integer
);

-- ===================== Carga (documento operacional) ==================
-- Caminhao no patio. Offline-first (uuid). sentido define o fluxo do kanban e
-- a ordem de pesagem (ENTRADA chega cheio; SAIDA chega vazio).
CREATE TABLE tblcarga (
  codcarga            serial PRIMARY KEY,
  uuid                varchar(36) NOT NULL UNIQUE,     -- gerado no cliente
  codsafra            integer NOT NULL REFERENCES tblsafra(codsafra),
  sentido             varchar(15) NOT NULL,            -- ENTRADA | SAIDA | TRANSFERENCIA
  etapa               varchar(20) NOT NULL,            -- PBT|TARA|CLASSIFICACAO|FISCAL|FINALIZADO
  data                timestamp NOT NULL,              -- chegada no patio
  placa               varchar(10),
  placacarreta        varchar(10),
  motorista           varchar(60),
  codveiculo          bigint REFERENCES tblveiculo(codveiculo),
  codpessoamotorista  bigint REFERENCES tblpessoa(codpessoa),
  pbt                 numeric(14,3),                   -- peso bruto total (caminhao + carga)
  tara                numeric(14,3),                   -- caminhao vazio
  bruto               numeric(14,3),                   -- pbt - tara (grao)
  umidade             numeric(6,3),                    -- %
  impureza            numeric(6,3),                    -- %
  avariados           numeric(6,3),                    -- %
  descontoumidade     numeric(14,3),                   -- kg
  descontoimpureza    numeric(14,3),                   -- kg
  descontoavariados   numeric(14,3),                   -- kg
  desconto            numeric(14,3),                   -- kg (soma dos descontos)
  liquido             numeric(14,3),                   -- bruto - desconto (seco)
  aprovado            timestamp,                       -- comprador aprovou (saida)
  observacao          text,
  inativo             timestamp,                       -- cancelamento/estorno
  criacao             timestamp,
  alteracao           timestamp,
  codusuariocriacao   integer,
  codusuarioalteracao integer
);
CREATE INDEX ix_carga_safra ON tblcarga (codsafra);
CREATE INDEX ix_carga_etapa ON tblcarga (etapa);

-- Origens e destinos da carga (o operador lanca aqui). O extrato e GERADO a
-- partir destes pontos. contatipo aponta para UMA das 3 contas.
CREATE TABLE tblcargaponto (
  codcargaponto          serial PRIMARY KEY,
  codcarga               integer NOT NULL REFERENCES tblcarga(codcarga) ON DELETE CASCADE,
  papel                  varchar(10) NOT NULL,         -- ORIGEM | DESTINO
  contatipo              varchar(10) NOT NULL,         -- PLANTIO | UNIDADE | CONTRATO
  codplantio             integer REFERENCES tblplantio(codplantio),
  codunidadearmazenadora integer REFERENCES tblunidadearmazenadora(codunidadearmazenadora),
  codcontrato            integer REFERENCES tblcontrato(codcontrato),
  liquido                numeric(14,3),                -- rateio (kg seco) deste ponto
  numeronf               varchar(20),                  -- NF por contrato (futuro)
  valornf                numeric(14,2),
  chavenf                varchar(44),
  criacao                timestamp,
  alteracao              timestamp,
  codusuariocriacao      integer,
  codusuarioalteracao    integer
);
CREATE INDEX ix_cargaponto_carga ON tblcargaponto (codcarga);

-- ===================== Movimento de grao (extrato/razao) ==============
-- GERADO a partir dos pontos da carga (idempotente) OU lancado manual
-- (ajuste comercial). saldo(conta) = SUM(liquido). liquido COM SINAL:
-- UNIDADE +entrada(destino)/-saida(origem); PLANTIO e CONTRATO contadores (+).
-- Auto = (codcarga not null, manual false); recalc regera so essas. Manual
-- (codcarga null, manual true) o recalc nunca toca.
CREATE TABLE tblmovimentograo (
  codmovimentograo       serial PRIMARY KEY,
  codcarga               integer REFERENCES tblcarga(codcarga) ON DELETE CASCADE,  -- null = manual
  manual                 boolean NOT NULL DEFAULT false,
  codsafra               integer REFERENCES tblsafra(codsafra),
  data                   timestamp NOT NULL,
  papel                  varchar(10) NOT NULL,         -- ORIGEM | DESTINO
  contatipo              varchar(10) NOT NULL,         -- PLANTIO | UNIDADE | CONTRATO
  codplantio             integer REFERENCES tblplantio(codplantio),
  codunidadearmazenadora integer REFERENCES tblunidadearmazenadora(codunidadearmazenadora),
  codcontrato            integer REFERENCES tblcontrato(codcontrato),
  bruto                  numeric(14,3) NOT NULL DEFAULT 0,   -- grao (pos-tara), com sinal
  desconto               numeric(14,3) NOT NULL DEFAULT 0,   -- classificacao, com sinal
  liquido                numeric(14,3) NOT NULL DEFAULT 0,   -- bruto - desconto, com sinal
  observacao             varchar(255),
  inativo                timestamp,                    -- so p/ manual (auto e regerado)
  criacao                timestamp,
  alteracao              timestamp,
  codusuariocriacao      integer,
  codusuarioalteracao    integer,
  CONSTRAINT ck_movimentograo_liquido CHECK (liquido = bruto - desconto)
);
CREATE INDEX ix_mov_contrato ON tblmovimentograo (contatipo, codcontrato);
CREATE INDEX ix_mov_unidade  ON tblmovimentograo (contatipo, codunidadearmazenadora);
CREATE INDEX ix_mov_plantio  ON tblmovimentograo (contatipo, codplantio);
CREATE INDEX ix_mov_carga    ON tblmovimentograo (codcarga);
CREATE INDEX ix_mov_safra    ON tblmovimentograo (codsafra);

COMMIT;

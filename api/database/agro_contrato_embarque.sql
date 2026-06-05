-- =====================================================================
-- Modulo Contrato (venda) + Embarque (expedicao) — controle de safra.
-- Schema mgsis. Convencoes tbl<nome>/cod<nome>, auditoria MgModel, inativo.
-- Domínios DDD: Mg\Contrato e Mg\Embarque.
-- =====================================================================

BEGIN;

-- ============================ Mg\Contrato ============================

-- Contrato de venda (FIXO / FIXAR / BARTER)
CREATE TABLE tblcontrato (
  codcontrato         serial PRIMARY KEY,
  contrato            varchar(30) NOT NULL,        -- numero/identificacao
  codpessoa           integer NOT NULL REFERENCES tblpessoa(codpessoa),  -- comprador
  codcultura          integer NOT NULL REFERENCES tblcultura(codcultura),
  codsafra            integer REFERENCES tblsafra(codsafra),
  tipo                varchar(10) NOT NULL,        -- FIXO | FIXAR | BARTER
  quantidade          numeric(14,3) NOT NULL,      -- sacas contratadas
  preco               numeric(14,4),               -- R$/saca (FIXO) ou referencia
  moeda               varchar(3) NOT NULL DEFAULT 'BRL',  -- BRL | USD
  dataembarque        date,                        -- limite/janela de embarque
  localentrega        varchar(80),                 -- FOB/CIF/local
  codnaturezaoperacao integer REFERENCES tblnaturezaoperacao(codnaturezaoperacao),
  codpessoanf         integer REFERENCES tblpessoa(codpessoa),  -- destinatario da NF
  observacao          text,
  observacaonf        text,
  inativo             timestamp,
  criacao             timestamp,
  alteracao           timestamp,
  codusuariocriacao   integer,
  codusuarioalteracao integer
);

-- Fixacoes de preco (parcial; USD com trava de R$)
CREATE TABLE tblcontratofixacao (
  codcontratofixacao  serial PRIMARY KEY,
  codcontrato         integer NOT NULL REFERENCES tblcontrato(codcontrato) ON DELETE CASCADE,
  data                date NOT NULL,
  quantidade          numeric(14,3) NOT NULL,      -- sacas fixadas
  preco               numeric(14,4) NOT NULL,      -- preco na moeda
  moeda               varchar(3) NOT NULL DEFAULT 'BRL',
  dolar               numeric(10,4),               -- taxa USD->BRL travada
  precoreal           numeric(14,4),               -- R$/saca resultante
  observacao          varchar(120),
  inativo             timestamp,
  criacao             timestamp,
  alteracao           timestamp,
  codusuariocriacao   integer,
  codusuarioalteracao integer
);

-- Pagamentos do comprador
CREATE TABLE tblcontratopagamento (
  codcontratopagamento serial PRIMARY KEY,
  codcontrato         integer NOT NULL REFERENCES tblcontrato(codcontrato) ON DELETE CASCADE,
  data                date NOT NULL,
  valor               numeric(14,2) NOT NULL,
  observacao          varchar(120),
  inativo             timestamp,
  criacao             timestamp,
  alteracao           timestamp,
  codusuariocriacao   integer,
  codusuarioalteracao integer
);

-- ============================ Mg\Embarque ============================

-- Embarque = carga de saida/expedicao (offline, Kanban de expedicao).
-- Etapas: PATIO -> TARA -> CLASSIFICACAO -> BRUTO -> FISCAL -> DESPACHADO.
CREATE TABLE tblembarque (
  codembarque         serial PRIMARY KEY,
  uuid                varchar(36) NOT NULL UNIQUE,
  etapa               varchar(20) NOT NULL,
  data                timestamp NOT NULL,          -- chegada
  placa               varchar(10),
  placacarreta        varchar(10),
  motorista           varchar(60),
  pesotara            numeric(14,3),
  pesobruto           numeric(14,3),
  pesoliquido         numeric(14,3),               -- bruto - tara
  umidade             numeric(6,3),
  impureza            numeric(6,3),
  avariados           numeric(6,3),
  descontoumidade     numeric(14,3),
  descontoimpureza    numeric(14,3),
  descontoavariados   numeric(14,3),
  pesoliquidoseco     numeric(14,3),
  aprovado            timestamp,                   -- comprador aprovou
  observacao          text,
  inativo             timestamp,
  criacao             timestamp,
  alteracao           timestamp,
  codusuariocriacao   integer,
  codusuarioalteracao integer
);

-- Contratos do embarque (rateio sc) + NF emitida por contrato
CREATE TABLE tblembarquecontrato (
  codembarquecontrato serial PRIMARY KEY,
  codembarque         integer NOT NULL REFERENCES tblembarque(codembarque) ON DELETE CASCADE,
  codcontrato         integer NOT NULL REFERENCES tblcontrato(codcontrato),
  quantidade          numeric(14,3) NOT NULL,      -- sacas deste contrato neste embarque
  codnotafiscal       integer REFERENCES tblnotafiscal(codnotafiscal),
  numeronf            varchar(20),
  valornf             numeric(14,2),
  chavenf             varchar(44),
  criacao             timestamp,
  alteracao           timestamp,
  codusuariocriacao   integer,
  codusuarioalteracao integer
);

-- Origem do grao (silo/armazem ou talhao; pode misturar)
CREATE TABLE tblembarqueorigem (
  codembarqueorigem   serial PRIMARY KEY,
  codembarque         integer NOT NULL REFERENCES tblembarque(codembarque) ON DELETE CASCADE,
  tipo                varchar(10) NOT NULL,        -- SILO | TALHAO
  codplantio          integer REFERENCES tblplantio(codplantio),
  quantidade          numeric(14,3),
  criacao             timestamp,
  alteracao           timestamp,
  codusuariocriacao   integer,
  codusuarioalteracao integer
);

COMMIT;

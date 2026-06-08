-- =====================================================================
-- Modulo Agro/Safra — controle de colheita
-- Schema mgsis. Convencoes: tbl<nome>, PK cod<nome> (serial), auditoria
-- via MgModel (criacao/alteracao + codusuariocriacao/alteracao), soft
-- delete via inativo (timestamp null = ativo).
-- MVP: cadastros de producao + carga de colheita (offline-first, uuid).
-- =====================================================================

BEGIN;

-- Cultura (Milho, Soja, ...) — peso da saca p/ produtividade
CREATE TABLE tblcultura (
  codcultura          serial PRIMARY KEY,
  cultura             varchar(30) NOT NULL,
  pesosaca            numeric(8,3) NOT NULL DEFAULT 60,   -- kg por saca
  inativo             timestamp,
  criacao             timestamp,
  alteracao           timestamp,
  codusuariocriacao   integer,
  codusuarioalteracao integer
);

-- Safra: cultura + periodo ("Milho 2a Safra 2026")
CREATE TABLE tblsafra (
  codsafra            serial PRIMARY KEY,
  codcultura          integer NOT NULL REFERENCES tblcultura(codcultura),
  safra               varchar(60) NOT NULL,
  datainicio          date,
  datafim             date,
  inativo             timestamp,
  criacao             timestamp,
  alteracao           timestamp,
  codusuariocriacao   integer,
  codusuarioalteracao integer
);

-- Fazenda/propriedade
CREATE TABLE tblfazenda (
  codfazenda          serial PRIMARY KEY,
  fazenda             varchar(60) NOT NULL,
  codpessoa           integer REFERENCES tblpessoa(codpessoa),  -- produtor rural (opcional)
  areatotal           numeric(12,4),                            -- hectares
  inativo             timestamp,
  criacao             timestamp,
  alteracao           timestamp,
  codusuariocriacao   integer,
  codusuarioalteracao integer
);

-- Variedade de semente
CREATE TABLE tblvariedade (
  codvariedade        serial PRIMARY KEY,
  codcultura          integer NOT NULL REFERENCES tblcultura(codcultura),
  variedade           varchar(60) NOT NULL,
  inativo             timestamp,
  criacao             timestamp,
  alteracao           timestamp,
  codusuariocriacao   integer,
  codusuarioalteracao integer
);

-- Talhao (parcela)
CREATE TABLE tbltalhao (
  codtalhao           serial PRIMARY KEY,
  codfazenda          integer NOT NULL REFERENCES tblfazenda(codfazenda),
  talhao              varchar(60) NOT NULL,
  area                numeric(12,4) NOT NULL,    -- hectares
  inativo             timestamp,
  criacao             timestamp,
  alteracao           timestamp,
  codusuariocriacao   integer,
  codusuarioalteracao integer
);

-- Plantio: o que esta plantado onde (talhao + safra + variedade)
CREATE TABLE tblplantio (
  codplantio          serial PRIMARY KEY,
  codsafra            integer NOT NULL REFERENCES tblsafra(codsafra),
  codtalhao           integer NOT NULL REFERENCES tbltalhao(codtalhao),
  codvariedade        integer NOT NULL REFERENCES tblvariedade(codvariedade),
  areaplantada        numeric(12,4) NOT NULL,    -- ha plantados (base da produtividade)
  dataplantio         date,
  inativo             timestamp,
  criacao             timestamp,
  alteracao           timestamp,
  codusuariocriacao   integer,
  codusuarioalteracao integer,
  UNIQUE (codsafra, codtalhao)
);

-- Tabela de desconto por faixa (umidade/impureza/avariados) por cultura
CREATE TABLE tbltabeladesconto (
  codtabeladesconto   serial PRIMARY KEY,
  codcultura          integer NOT NULL REFERENCES tblcultura(codcultura),
  tipo                varchar(12) NOT NULL,       -- 'UMIDADE' | 'IMPUREZA' | 'AVARIADOS'
  faixainicio         numeric(6,3) NOT NULL,      -- % de (inclusive)
  faixafim            numeric(6,3) NOT NULL,       -- % ate (inclusive)
  percentualdesconto  numeric(6,3) NOT NULL,       -- % aplicado sobre o peso liquido
  inativo             timestamp,
  criacao             timestamp,
  alteracao           timestamp,
  codusuariocriacao   integer,
  codusuarioalteracao integer
);

-- Carga de colheita = visita de caminhao no patio de recebimento (offline-first,
-- sync por uuid). Preenchida em etapas (Kanban): PATIO -> BRUTO -> CLASSIFICACAO
-- -> TARA -> FINALIZADO. Pesos/classificacao nullable (chegam ao longo do patio).
CREATE TABLE tblcargacolheita (
  codcargacolheita    serial PRIMARY KEY,
  uuid                varchar(36) NOT NULL UNIQUE,    -- gerado no cliente
  codsafra            integer NOT NULL REFERENCES tblsafra(codsafra),
  etapa               varchar(20) NOT NULL,           -- PATIO|BRUTO|CLASSIFICACAO|TARA|FINALIZADO
  data                timestamp NOT NULL,             -- chegada no patio
  placa               varchar(10),
  motorista           varchar(60),
  pesobruto           numeric(14,3),                  -- kg
  tara                numeric(14,3),                  -- kg
  pesoliquido         numeric(14,3),                  -- kg (bruto - tara)
  umidade             numeric(6,3),                   -- %
  impureza            numeric(6,3),                   -- %
  avariados           numeric(6,3),                   -- %
  descontoumidade     numeric(14,3),                  -- kg
  descontoimpureza    numeric(14,3),                  -- kg
  descontoavariados   numeric(14,3),                  -- kg
  pesoliquidoseco     numeric(14,3),                  -- kg final
  observacao          text,
  inativo             timestamp,                      -- cancelamento/estorno
  criacao             timestamp,
  alteracao           timestamp,
  codusuariocriacao   integer,
  codusuarioalteracao integer
);

-- Talhoes que compoem a carga (mistura), com rateio por percentual (soma 100)
CREATE TABLE tblcargacolheitaplantio (
  codcargacolheitaplantio serial PRIMARY KEY,
  codcargacolheita    integer NOT NULL REFERENCES tblcargacolheita(codcargacolheita) ON DELETE CASCADE,
  codplantio          integer NOT NULL REFERENCES tblplantio(codplantio),
  percentual          numeric(6,3) NOT NULL,
  criacao             timestamp,
  alteracao           timestamp,
  codusuariocriacao   integer,
  codusuarioalteracao integer
);

COMMIT;

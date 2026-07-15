-- =============================================================================
-- App Agro — Classificação de Grãos por FÓRMULA (catálogo + tabelas N:N)
-- =============================================================================
-- Troca o motor de FAIXAS FIXAS (tbltabeladesconto) por um modelo normalizado
-- dirigido por cadastro:
--   tblparametroclassificacao   -> catálogo de parâmetros (tipos + método/reduzbase)
--   tbltabelaclassificacao      -> tabela nomeada por cultura ("Padrão Milho")
--   tbltabelaclassificacaoitem  -> N:N valores (ordem/tolerância/fator/deságio)
--   tblcargaclassificacao       -> N:N leituras da carga (leitura/desconto)
-- FK simétrica codtabelaclassificacao em cultura/contrato/carga (padrão escolhido).
-- O desconto é FÓRMULA EM CASCATA calculada no CargaService (autoridade) e
-- replicada offline no utils/desconto.js.
--
-- Rodar:
--   docker exec -i mgdb-mgdb-1 psql -U mgsis -d mgsis < database/agro_classificacao.sql
-- =============================================================================

BEGIN;

-- 1) CATÁLOGO de parâmetros -----------------------------------------------------
CREATE TABLE tblparametroclassificacao (
    codparametroclassificacao serial PRIMARY KEY,
    parametroclassificacao    varchar(40)  NOT NULL,
    metodo                    varchar(12)  NOT NULL DEFAULT 'NORMALIZADO', -- FATOR | NORMALIZADO
    reduzbase                 boolean      NOT NULL DEFAULT false,
    inativo                   timestamp without time zone,
    criacao                   timestamp without time zone,
    alteracao                 timestamp without time zone,
    codusuariocriacao         integer,
    codusuarioalteracao       integer
);

INSERT INTO tblparametroclassificacao (parametroclassificacao, metodo, reduzbase, criacao, alteracao) VALUES
    ('Impureza',    'NORMALIZADO', true,  now(), now()),
    ('Umidade',     'FATOR',       true,  now(), now()),
    ('Avariados',   'NORMALIZADO', false, now(), now()),
    ('Esverdeados', 'NORMALIZADO', false, now(), now()),
    ('Quebrados',   'NORMALIZADO', false, now(), now());

-- 2) TABELA de classificação (padrão nomeado por cultura) -----------------------
CREATE TABLE tbltabelaclassificacao (
    codtabelaclassificacao serial PRIMARY KEY,
    codcultura             integer      NOT NULL REFERENCES tblcultura (codcultura),
    tabelaclassificacao    varchar(60)  NOT NULL,
    inativo                timestamp without time zone,
    criacao                timestamp without time zone,
    alteracao              timestamp without time zone,
    codusuariocriacao      integer,
    codusuarioalteracao    integer
);

-- 3) ITENS da tabela (N:N tabela × parâmetro, com os valores) -------------------
CREATE TABLE tbltabelaclassificacaoitem (
    codtabelaclassificacaoitem serial PRIMARY KEY,
    codtabelaclassificacao     integer      NOT NULL REFERENCES tbltabelaclassificacao (codtabelaclassificacao),
    codparametroclassificacao  integer      NOT NULL REFERENCES tblparametroclassificacao (codparametroclassificacao),
    ordem                      integer      NOT NULL DEFAULT 0,
    tolerancia                 numeric(6,3) NOT NULL DEFAULT 0,
    fator                      numeric(6,3) NOT NULL DEFAULT 0,   -- usado quando metodo = FATOR
    desagio                    numeric(6,3) NOT NULL DEFAULT 0,   -- usado quando metodo = NORMALIZADO
    criacao                    timestamp without time zone,
    alteracao                  timestamp without time zone,
    codusuariocriacao          integer,
    codusuarioalteracao        integer,
    UNIQUE (codtabelaclassificacao, codparametroclassificacao)
);

-- 4) FK do padrão escolhido (simétrica) + leituras da carga ---------------------
ALTER TABLE tblcultura  ADD COLUMN codtabelaclassificacao integer NULL
    REFERENCES tbltabelaclassificacao (codtabelaclassificacao);   -- padrão da cultura
ALTER TABLE tblcontrato ADD COLUMN codtabelaclassificacao integer NULL
    REFERENCES tbltabelaclassificacao (codtabelaclassificacao);   -- do contrato
ALTER TABLE tblcarga    ADD COLUMN codtabelaclassificacao integer NULL
    REFERENCES tbltabelaclassificacao (codtabelaclassificacao);   -- resolvida na carga

CREATE TABLE tblcargaclassificacao (
    codcargaclassificacao     serial PRIMARY KEY,
    codcarga                  integer       NOT NULL REFERENCES tblcarga (codcarga),
    codparametroclassificacao integer       NOT NULL REFERENCES tblparametroclassificacao (codparametroclassificacao),
    leitura                   numeric(6,3),   -- %
    desconto                  numeric(14,3),  -- kg (derivado)
    criacao                   timestamp without time zone,
    alteracao                 timestamp without time zone,
    codusuariocriacao         integer,
    codusuarioalteracao       integer,
    UNIQUE (codcarga, codparametroclassificacao)
);

-- migra as leituras existentes (umidade/impureza/avariados) p/ a filha, antes do drop
INSERT INTO tblcargaclassificacao (codcarga, codparametroclassificacao, leitura, desconto, criacao, alteracao)
SELECT c.codcarga, p.codparametroclassificacao, c.umidade, c.descontoumidade, now(), now()
  FROM tblcarga c CROSS JOIN tblparametroclassificacao p
 WHERE p.parametroclassificacao = 'Umidade' AND c.umidade IS NOT NULL;
INSERT INTO tblcargaclassificacao (codcarga, codparametroclassificacao, leitura, desconto, criacao, alteracao)
SELECT c.codcarga, p.codparametroclassificacao, c.impureza, c.descontoimpureza, now(), now()
  FROM tblcarga c CROSS JOIN tblparametroclassificacao p
 WHERE p.parametroclassificacao = 'Impureza' AND c.impureza IS NOT NULL;
INSERT INTO tblcargaclassificacao (codcarga, codparametroclassificacao, leitura, desconto, criacao, alteracao)
SELECT c.codcarga, p.codparametroclassificacao, c.avariados, c.descontoavariados, now(), now()
  FROM tblcarga c CROSS JOIN tblparametroclassificacao p
 WHERE p.parametroclassificacao = 'Avariados' AND c.avariados IS NOT NULL;

ALTER TABLE tblcarga
    DROP COLUMN umidade,
    DROP COLUMN impureza,
    DROP COLUMN avariados,
    DROP COLUMN descontoumidade,
    DROP COLUMN descontoimpureza,
    DROP COLUMN descontoavariados;

-- 5) Seed: 1 tabela "Padrão <Cultura>" por cultura + itens, e cultura aponta -----
INSERT INTO tbltabelaclassificacao (codcultura, tabelaclassificacao, criacao, alteracao)
SELECT c.codcultura, 'Padrão ' || c.cultura, now(), now()
  FROM tblcultura c WHERE c.inativo IS NULL;

-- defaults por parâmetro (avariados 8 soja / 6 milho; umidade tol 14 fator 1,5; impureza tol 1)
INSERT INTO tbltabelaclassificacaoitem
    (codtabelaclassificacao, codparametroclassificacao, ordem, tolerancia, fator, desagio, criacao, alteracao)
SELECT t.codtabelaclassificacao, p.codparametroclassificacao,
       CASE p.parametroclassificacao
            WHEN 'Impureza' THEN 1 WHEN 'Umidade' THEN 2 WHEN 'Avariados' THEN 3
            WHEN 'Esverdeados' THEN 4 WHEN 'Quebrados' THEN 5 ELSE 9 END,
       CASE p.parametroclassificacao
            WHEN 'Umidade' THEN 14 WHEN 'Impureza' THEN 1
            WHEN 'Avariados' THEN (CASE WHEN lower(cu.cultura) LIKE '%soja%' THEN 8 ELSE 6 END)
            ELSE 0 END,
       CASE p.parametroclassificacao WHEN 'Umidade' THEN 1.5 ELSE 0 END,
       0,
       now(), now()
  FROM tbltabelaclassificacao t
  JOIN tblcultura cu ON cu.codcultura = t.codcultura
 CROSS JOIN tblparametroclassificacao p;

UPDATE tblcultura c
   SET codtabelaclassificacao = t.codtabelaclassificacao
  FROM tbltabelaclassificacao t
 WHERE t.codcultura = c.codcultura AND c.codtabelaclassificacao IS NULL;

-- 6) Aposenta a tabela de faixas antiga ----------------------------------------
DROP TABLE tbltabeladesconto;

COMMIT;

-- Conferência:
-- SELECT c.cultura, t.tabelaclassificacao, pa.parametroclassificacao, pa.metodo,
--        pa.reduzbase, i.ordem, i.tolerancia, i.fator, i.desagio
--   FROM tblcultura c
--   JOIN tbltabelaclassificacao t     ON t.codtabelaclassificacao = c.codtabelaclassificacao
--   JOIN tbltabelaclassificacaoitem i ON i.codtabelaclassificacao = t.codtabelaclassificacao
--   JOIN tblparametroclassificacao pa ON pa.codparametroclassificacao = i.codparametroclassificacao
--  ORDER BY c.cultura, i.ordem;

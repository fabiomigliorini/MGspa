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
-- IDEMPOTENTE: seguro rodar mais de uma vez (IF NOT EXISTS / ON CONFLICT / NOT
-- EXISTS). Rodar duas vezes NÃO cria tabelas "Padrão" duplicadas.
--
-- Rodar:
--   docker exec -i mgdb-mgdb-1 psql -U mgsis -d mgsis < database/agro_classificacao.sql
-- =============================================================================

BEGIN;

-- 1) CATÁLOGO de parâmetros -----------------------------------------------------
CREATE TABLE IF NOT EXISTS tblparametroclassificacao (
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

-- Seed só quando o catálogo está vazio (não duplica numa re-execução).
INSERT INTO tblparametroclassificacao (parametroclassificacao, metodo, reduzbase, criacao, alteracao)
SELECT v.parametroclassificacao, v.metodo, v.reduzbase, now(), now()
  FROM (VALUES
      ('Impureza',    'NORMALIZADO', true),
      ('Umidade',     'FATOR',       true),
      ('Avariados',   'NORMALIZADO', false),
      ('Esverdeados', 'NORMALIZADO', false),
      ('Quebrados',   'NORMALIZADO', false)
  ) AS v(parametroclassificacao, metodo, reduzbase)
 WHERE NOT EXISTS (SELECT 1 FROM tblparametroclassificacao);

-- 2) TABELA de classificação (padrão nomeado por cultura) -----------------------
CREATE TABLE IF NOT EXISTS tbltabelaclassificacao (
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
CREATE TABLE IF NOT EXISTS tbltabelaclassificacaoitem (
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
ALTER TABLE tblcultura  ADD COLUMN IF NOT EXISTS codtabelaclassificacao integer NULL
    REFERENCES tbltabelaclassificacao (codtabelaclassificacao);   -- padrão da cultura
ALTER TABLE tblcontrato ADD COLUMN IF NOT EXISTS codtabelaclassificacao integer NULL
    REFERENCES tbltabelaclassificacao (codtabelaclassificacao);   -- do contrato
ALTER TABLE tblcarga    ADD COLUMN IF NOT EXISTS codtabelaclassificacao integer NULL
    REFERENCES tbltabelaclassificacao (codtabelaclassificacao);   -- resolvida na carga

CREATE TABLE IF NOT EXISTS tblcargaclassificacao (
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

-- Migração das leituras legadas (umidade/impureza/avariados) p/ a filha + DROP das
-- colunas antigas. Só roda se as colunas AINDA existem (já migrado = pula tudo).
DO $$
BEGIN
    -- Só migra se o CONJUNTO COMPLETO de colunas legadas existe (as 6). Num schema
    -- editado à mão (ex.: umidade presente mas descontoumidade não), pula em vez de
    -- ler uma coluna inexistente.
    IF (
        SELECT count(*) FROM information_schema.columns
         WHERE table_name = 'tblcarga'
           AND column_name IN ('umidade', 'impureza', 'avariados',
                               'descontoumidade', 'descontoimpureza', 'descontoavariados')
    ) = 6 THEN
        INSERT INTO tblcargaclassificacao (codcarga, codparametroclassificacao, leitura, desconto, criacao, alteracao)
        SELECT c.codcarga, p.codparametroclassificacao, c.umidade, c.descontoumidade, now(), now()
          FROM tblcarga c CROSS JOIN tblparametroclassificacao p
         WHERE p.parametroclassificacao = 'Umidade' AND c.umidade IS NOT NULL
        ON CONFLICT (codcarga, codparametroclassificacao) DO NOTHING;

        INSERT INTO tblcargaclassificacao (codcarga, codparametroclassificacao, leitura, desconto, criacao, alteracao)
        SELECT c.codcarga, p.codparametroclassificacao, c.impureza, c.descontoimpureza, now(), now()
          FROM tblcarga c CROSS JOIN tblparametroclassificacao p
         WHERE p.parametroclassificacao = 'Impureza' AND c.impureza IS NOT NULL
        ON CONFLICT (codcarga, codparametroclassificacao) DO NOTHING;

        INSERT INTO tblcargaclassificacao (codcarga, codparametroclassificacao, leitura, desconto, criacao, alteracao)
        SELECT c.codcarga, p.codparametroclassificacao, c.avariados, c.descontoavariados, now(), now()
          FROM tblcarga c CROSS JOIN tblparametroclassificacao p
         WHERE p.parametroclassificacao = 'Avariados' AND c.avariados IS NOT NULL
        ON CONFLICT (codcarga, codparametroclassificacao) DO NOTHING;

        ALTER TABLE tblcarga
            DROP COLUMN IF EXISTS umidade,
            DROP COLUMN IF EXISTS impureza,
            DROP COLUMN IF EXISTS avariados,
            DROP COLUMN IF EXISTS descontoumidade,
            DROP COLUMN IF EXISTS descontoimpureza,
            DROP COLUMN IF EXISTS descontoavariados;
    END IF;
END $$;

-- 5) Seed: 1 tabela "Padrão <Cultura>" por cultura + itens, e cultura aponta -----
-- NOT EXISTS por cultura: não recria a "Padrão" se a cultura já tem tabela (evita
-- as 4x duplicadas que a versão antiga gerava numa re-execução).
INSERT INTO tbltabelaclassificacao (codcultura, tabelaclassificacao, criacao, alteracao)
SELECT c.codcultura, 'Padrão ' || c.cultura, now(), now()
  FROM tblcultura c
 WHERE c.inativo IS NULL
   AND NOT EXISTS (SELECT 1 FROM tbltabelaclassificacao x WHERE x.codcultura = c.codcultura);

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
 CROSS JOIN tblparametroclassificacao p
ON CONFLICT (codtabelaclassificacao, codparametroclassificacao) DO NOTHING;

UPDATE tblcultura c
   SET codtabelaclassificacao = (
        SELECT MIN(t.codtabelaclassificacao)
          FROM tbltabelaclassificacao t
         WHERE t.codcultura = c.codcultura
   )
 WHERE c.codtabelaclassificacao IS NULL
   AND EXISTS (SELECT 1 FROM tbltabelaclassificacao t WHERE t.codcultura = c.codcultura);

-- 6) Aposenta a tabela de faixas antiga ----------------------------------------
DROP TABLE IF EXISTS tbltabeladesconto;

COMMIT;

-- Conferência:
-- SELECT c.cultura, t.tabelaclassificacao, pa.parametroclassificacao, pa.metodo,
--        pa.reduzbase, i.ordem, i.tolerancia, i.fator, i.desagio
--   FROM tblcultura c
--   JOIN tbltabelaclassificacao t     ON t.codtabelaclassificacao = c.codtabelaclassificacao
--   JOIN tbltabelaclassificacaoitem i ON i.codtabelaclassificacao = t.codtabelaclassificacao
--   JOIN tblparametroclassificacao pa ON pa.codparametroclassificacao = i.codparametroclassificacao
--  ORDER BY c.cultura, i.ordem;

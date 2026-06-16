-- =====================================================================
-- Modulo de tributacao do AGRO (dono da inteligencia fiscal de graos).
-- Schema mgsis. Convencoes tbl<nome>/cod<nome>, auditoria MgModel, inativo.
--
-- O agro DECIDE se a operacao tem FETHAB/IAGRO/Funrural/Senar, quanto e,
-- isencao e folha/venda; o app `notas` so RECEBE os valores prontos
-- (tblnotafiscalitemtributo) e emite. NAO ha motor fiscal paralelo no
-- notas para operacoes vindas do agro.
--
-- Tributos rurais reusam o catalogo generico tbltributo (FETHAB/IAGRO =
-- ESTADUAL, FUNRURAL/SENAR = FEDERAL). FETHAB/IAGRO sao indexados a uma
-- UNIDADE DE REFERENCIA (UPF-MT); Senar/Funrural sao % do valor.
--
-- Reaplicavel: usa IF NOT EXISTS / INSERT ... WHERE NOT EXISTS.
-- =====================================================================

BEGIN;

-- ============== Unidades de referencia fiscal (UPF, UR, ...) ==========

-- Catalogo: cada unidade pertence a um ente (estado/municipio/uniao).
-- Ex.: UPF-MT (ESTADUAL, codestado MT), UR-Sinop (MUNICIPAL, codcidade).
CREATE TABLE IF NOT EXISTS tblunidadereferencia (
  codunidadereferencia serial PRIMARY KEY,
  codigo              varchar(10) NOT NULL,        -- UPF | UR | UFIR ...
  descricao           varchar(100) NOT NULL,
  ente                varchar(15) NOT NULL,        -- FEDERAL | ESTADUAL | MUNICIPAL
  codestado           integer REFERENCES tblestado(codestado),
  codcidade           integer REFERENCES tblcidade(codcidade),
  inativo             timestamp,
  criacao             timestamp DEFAULT now(),
  alteracao           timestamp DEFAULT now(),
  codusuariocriacao   integer,
  codusuarioalteracao integer,
  CONSTRAINT chk_unidadereferencia_ente
    CHECK (ente IN ('FEDERAL', 'ESTADUAL', 'MUNICIPAL'))
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_unidadereferencia_unico
  ON tblunidadereferencia (codigo, COALESCE(codestado, 0), COALESCE(codcidade, 0));

-- Historico de valores por competencia (UPF-MT muda mensal; UR anual).
CREATE TABLE IF NOT EXISTS tblunidadereferenciavalor (
  codunidadereferenciavalor serial PRIMARY KEY,
  codunidadereferencia integer NOT NULL REFERENCES tblunidadereferencia(codunidadereferencia) ON DELETE CASCADE,
  competencia         date NOT NULL,               -- 1o dia do periodo de vigencia
  valor               numeric(14,4) NOT NULL,
  criacao             timestamp DEFAULT now(),
  alteracao           timestamp DEFAULT now(),
  codusuariocriacao   integer,
  codusuarioalteracao integer
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_unidadereferenciavalor_unico
  ON tblunidadereferenciavalor (codunidadereferencia, competencia);

-- ================ Config de tributos por cultura =====================

-- Quais tributos incidem em cada cultura e como se calculam:
--   base UNIDADE -> percentual/100 x valorUnidade(competencia) x pesosaca/1000
--   base VALOR   -> percentual/100 x valorBruto
-- grupofethab: pula quando o contrato e isento de FETHAB (cooperativa).
-- funrural:    so aplica quando a filial paga Funrural na venda.
CREATE TABLE IF NOT EXISTS tblculturatributo (
  codculturatributo   serial PRIMARY KEY,
  codcultura          integer NOT NULL REFERENCES tblcultura(codcultura) ON DELETE CASCADE,
  codtributo          integer NOT NULL REFERENCES tbltributo(codtributo),
  base                varchar(10) NOT NULL,        -- VALOR | UNIDADE
  codunidadereferencia integer REFERENCES tblunidadereferencia(codunidadereferencia),
  percentual          numeric(10,5) NOT NULL,      -- % (sobre valor ou sobre a unidade)
  grupofethab         boolean NOT NULL DEFAULT false,
  funrural            boolean NOT NULL DEFAULT false,
  ordem               smallint NOT NULL DEFAULT 0,
  inativo             timestamp,
  criacao             timestamp DEFAULT now(),
  alteracao           timestamp DEFAULT now(),
  codusuariocriacao   integer,
  codusuarioalteracao integer,
  CONSTRAINT chk_culturatributo_base CHECK (base IN ('VALOR', 'UNIDADE'))
);
CREATE INDEX IF NOT EXISTS idx_culturatributo_cultura ON tblculturatributo (codcultura);

-- ================ Flags em tabelas existentes ========================

-- Funrural na folha (default) x na venda — propriedade do produtor (filial).
ALTER TABLE tblfilial   ADD COLUMN IF NOT EXISTS funruralvenda boolean NOT NULL DEFAULT false;
-- Contrato isento de FETHAB (ex.: venda via cooperativa / Cooasin).
ALTER TABLE tblcontrato ADD COLUMN IF NOT EXISTS isentofethab boolean NOT NULL DEFAULT false;

-- ============================ SEEDS ==================================

-- Tributos rurais no catalogo generico (unique codigo+ente).
INSERT INTO tbltributo (codigo, descricao, ente, criacao, alteracao)
SELECT 'FETHAB', 'Fundo Estadual de Transporte e Habitacao', 'ESTADUAL', now(), now()
WHERE NOT EXISTS (SELECT 1 FROM tbltributo WHERE codigo = 'FETHAB' AND ente = 'ESTADUAL');
INSERT INTO tbltributo (codigo, descricao, ente, criacao, alteracao)
SELECT 'IAGRO', 'Instituto de Defesa Agropecuaria (MT)', 'ESTADUAL', now(), now()
WHERE NOT EXISTS (SELECT 1 FROM tbltributo WHERE codigo = 'IAGRO' AND ente = 'ESTADUAL');
INSERT INTO tbltributo (codigo, descricao, ente, criacao, alteracao)
SELECT 'FUNRURAL', 'Contribuicao Previdenciaria Rural', 'FEDERAL', now(), now()
WHERE NOT EXISTS (SELECT 1 FROM tbltributo WHERE codigo = 'FUNRURAL' AND ente = 'FEDERAL');
INSERT INTO tbltributo (codigo, descricao, ente, criacao, alteracao)
SELECT 'SENAR', 'Servico Nacional de Aprendizagem Rural', 'FEDERAL', now(), now()
WHERE NOT EXISTS (SELECT 1 FROM tbltributo WHERE codigo = 'SENAR' AND ente = 'FEDERAL');

-- Unidade UPF-MT (estadual, Mato Grosso = codestado 8956).
INSERT INTO tblunidadereferencia (codigo, descricao, ente, codestado, criacao, alteracao)
SELECT 'UPF', 'UPF-MT (Unidade Padrao Fiscal de Mato Grosso)', 'ESTADUAL', 8956, now(), now()
WHERE NOT EXISTS (
  SELECT 1 FROM tblunidadereferencia WHERE codigo = 'UPF' AND codestado = 8956
);

-- Historico UPF-MT (valores reais publicados pela SEFAZ-MT / TJMT).
-- O scraper (Fase 5) mantem isso atualizado automaticamente.
INSERT INTO tblunidadereferenciavalor (codunidadereferencia, competencia, valor, criacao, alteracao)
SELECT ur.codunidadereferencia, v.competencia::date, v.valor, now(), now()
FROM tblunidadereferencia ur
CROSS JOIN (VALUES
  ('2025-01-01', 243.49),
  ('2026-01-01', 254.36),
  ('2026-02-01', 255.20),
  ('2026-03-01', 256.04),
  ('2026-05-01', 260.10),
  ('2026-06-01', 261.84)
) AS v(competencia, valor)
WHERE ur.codigo = 'UPF' AND ur.codestado = 8956
  AND NOT EXISTS (
    SELECT 1 FROM tblunidadereferenciavalor x
    WHERE x.codunidadereferencia = ur.codunidadereferencia
      AND x.competencia = v.competencia::date
  );

-- Config por cultura (Soja / Milho). Idempotente por (cultura, tributo, ordem).
-- FETHAB combinado (soja 20% = 2 x 10% da planilha; milho 6%) porque
-- tblnotafiscalitemtributo e unica por (item, tributo).
INSERT INTO tblculturatributo (codcultura, codtributo, base, codunidadereferencia, percentual, grupofethab, funrural, ordem, criacao, alteracao)
SELECT c.codcultura, t.codtributo, s.base,
       CASE WHEN s.base = 'UNIDADE' THEN ur.codunidadereferencia END,
       s.percentual, s.grupofethab, s.funrural, s.ordem, now(), now()
FROM (VALUES
  ('Soja',  'FETHAB',   'UNIDADE', 20.0, true,  false, 1),
  ('Soja',  'IAGRO',    'UNIDADE', 1.15, true,  false, 2),
  ('Soja',  'SENAR',    'VALOR',   0.20, false, false, 3),
  ('Soja',  'FUNRURAL', 'VALOR',   1.30, false, true,  4),
  ('Milho', 'FETHAB',   'UNIDADE', 6.0,  true,  false, 1),
  ('Milho', 'SENAR',    'VALOR',   0.20, false, false, 2),
  ('Milho', 'FUNRURAL', 'VALOR',   1.30, false, true,  3)
) AS s(cultura, tributo, base, percentual, grupofethab, funrural, ordem)
JOIN tblcultura c ON c.cultura = s.cultura
JOIN tbltributo t ON t.codigo = s.tributo
LEFT JOIN tblunidadereferencia ur ON ur.codigo = 'UPF' AND ur.codestado = 8956
WHERE NOT EXISTS (
  SELECT 1 FROM tblculturatributo ct
  WHERE ct.codcultura = c.codcultura AND ct.codtributo = t.codtributo AND ct.ordem = s.ordem
);

COMMIT;

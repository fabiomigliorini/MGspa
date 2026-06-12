-- Safra: troca o período (datainicio/datafim date) por ano de plantio e ano de
-- colheita (smallint). Ano de plantio é único por cultura. A cultura passa a
-- guardar o ciclo em anos civis (1 = planta e colhe no mesmo ano, ex.: milho
-- safrinha; 2 = planta num ano e colhe no seguinte, ex.: soja), usado pra
-- sugerir o ano de colheita ao abrir uma nova safra.
-- Rodar no dev e na produção.

BEGIN;

-- Ciclo da cultura (anos civis que a safra cruza)
ALTER TABLE tblcultura
  ADD COLUMN IF NOT EXISTS cicloanos smallint NOT NULL DEFAULT 1;

-- Novos campos da safra
ALTER TABLE tblsafra ADD COLUMN IF NOT EXISTS anoplantio  smallint;
ALTER TABLE tblsafra ADD COLUMN IF NOT EXISTS anocolheita smallint;

-- Migra os dados existentes (extrai o ano das datas antigas)
UPDATE tblsafra SET anoplantio  = EXTRACT(YEAR FROM datainicio)::smallint
 WHERE anoplantio IS NULL AND datainicio IS NOT NULL;
UPDATE tblsafra SET anocolheita = EXTRACT(YEAR FROM datafim)::smallint
 WHERE anocolheita IS NULL AND datafim IS NOT NULL;

-- Remove o período antigo
ALTER TABLE tblsafra DROP COLUMN IF EXISTS datainicio;
ALTER TABLE tblsafra DROP COLUMN IF EXISTS datafim;

-- Ano de plantio único por cultura (não pode haver sobreposição)
ALTER TABLE tblsafra
  ADD CONSTRAINT uk_safra_cultura_anoplantio UNIQUE (codcultura, anoplantio);

COMMIT;

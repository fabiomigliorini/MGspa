-- Safra: troca o período (datainicio/datafim date) por ano de plantio e ano de
-- colheita (smallint). Ano de plantio é único por cultura. A cultura passa a
-- guardar o ciclo em anos civis (1 = planta e colhe no mesmo ano, ex.: milho
-- safrinha; 2 = planta num ano e colhe no seguinte, ex.: soja), usado pra
-- sugerir o ano de colheita ao abrir uma nova safra.
-- Rodar no dev e na produção.
--
-- GUARDA: pula se tblsafra ja esta em anos e o UNIQUE tem o nome padrao. Na
-- 2a rodada o ADD CONSTRAINT falhava.
-- Bloco unico (atomico); sem BEGIN/COMMIT pra nao deixar transacao aberta no DBeaver.
-- =====================================================================

SET lock_timeout = '5s';

DO $agro$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblsafra' AND column_name = 'datainicio') AND EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'uk_safra_cultura_anoplantio') THEN
    RAISE NOTICE '05 safra_ano: pulado (safra ja esta em anos)';
    RETURN;
  END IF;

  -- Ciclo da cultura (anos civis que a safra cruza)
  ALTER TABLE tblcultura
    ADD COLUMN IF NOT EXISTS cicloanos smallint NOT NULL DEFAULT 1;

  -- Novos campos da safra
  ALTER TABLE tblsafra ADD COLUMN IF NOT EXISTS anoplantio  smallint;
  ALTER TABLE tblsafra ADD COLUMN IF NOT EXISTS anocolheita smallint;

  -- Migra os dados existentes (extrai o ano das datas antigas) e remove o periodo.
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblsafra' AND column_name = 'datainicio') THEN
    EXECUTE 'UPDATE tblsafra SET anoplantio  = EXTRACT(YEAR FROM datainicio)::smallint
              WHERE anoplantio IS NULL AND datainicio IS NOT NULL';
    EXECUTE 'UPDATE tblsafra SET anocolheita = EXTRACT(YEAR FROM datafim)::smallint
              WHERE anocolheita IS NULL AND datafim IS NOT NULL';
    ALTER TABLE tblsafra DROP COLUMN IF EXISTS datainicio;
    ALTER TABLE tblsafra DROP COLUMN IF EXISTS datafim;
  END IF;

  -- Ano de plantio único por cultura (não pode haver sobreposição). A base criada
  -- pelo agro.sql de 11/06 ja tem o UNIQUE com o nome automatico: so renomeia.
  IF EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'tblsafra_codcultura_anoplantio_key') THEN
    ALTER TABLE tblsafra RENAME CONSTRAINT tblsafra_codcultura_anoplantio_key TO uk_safra_cultura_anoplantio;
  ELSE
    ALTER TABLE tblsafra
      ADD CONSTRAINT uk_safra_cultura_anoplantio UNIQUE (codcultura, anoplantio);
  END IF;

  RAISE NOTICE '05 safra_ano: aplicado';
END
$agro$;

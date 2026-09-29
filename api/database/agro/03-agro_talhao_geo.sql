-- ============================================================
-- Agro: coordenadas geográficas do talhão (polígono + centro)
-- Rodar no dev e na PROD. PostgreSQL.
-- ============================================================
--
-- GUARDA: pula se as 4 colunas ja existem.
-- Bloco unico (atomico); sem BEGIN/COMMIT pra nao deixar transacao aberta no DBeaver.
-- =====================================================================

SET lock_timeout = '5s';

DO $agro$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tbltalhao' AND column_name = 'geometria') AND EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tbltalhao' AND column_name = 'latitude') AND EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tbltalhao' AND column_name = 'longitude') AND EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tbltalhao' AND column_name = 'cor') THEN
    RAISE NOTICE '03 talhao_geo: pulado (colunas ja existem)';
    RETURN;
  END IF;

  ALTER TABLE tbltalhao ADD COLUMN IF NOT EXISTS geometria jsonb NULL;     -- GeoJSON Polygon
  ALTER TABLE tbltalhao ADD COLUMN IF NOT EXISTS latitude  numeric NULL;   -- centro do polígono
  ALTER TABLE tbltalhao ADD COLUMN IF NOT EXISTS longitude numeric NULL;
  ALTER TABLE tbltalhao ADD COLUMN IF NOT EXISTS cor       varchar(9) NULL; -- cor do talhão no mapa (#RRGGBB)

  RAISE NOTICE '03 talhao_geo: aplicado';
END
$agro$;

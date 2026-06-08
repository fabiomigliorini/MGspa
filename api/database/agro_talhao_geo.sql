-- ============================================================
-- Agro: coordenadas geográficas do talhão (polígono + centro)
-- Rodar no dev e na PROD. PostgreSQL.
-- ============================================================

ALTER TABLE tbltalhao ADD COLUMN IF NOT EXISTS geometria jsonb NULL;     -- GeoJSON Polygon
ALTER TABLE tbltalhao ADD COLUMN IF NOT EXISTS latitude  numeric NULL;   -- centro do polígono
ALTER TABLE tbltalhao ADD COLUMN IF NOT EXISTS longitude numeric NULL;
ALTER TABLE tbltalhao ADD COLUMN IF NOT EXISTS cor       varchar(9) NULL; -- cor do talhão no mapa (#RRGGBB)

-- ============================================================
-- Agro: geometria por SAFRA no plantio.
--
-- Mudanca de modelagem: o desenho/divisao do talhao NAO e fixo, muda a
-- cada safra (orientacao da plantadeira, subdivisao, unificacao, renumeracao).
-- Por isso a geometria passa a viver no tblplantio (o "talhao desta safra").
-- O tbltalhao continua existindo como LAYOUT BASE da fazenda (modelo que se
-- desenha 1x e cada safra clona pros plantios e edita por cima).
--
-- Rodar no dev e na PROD. PostgreSQL. Idempotente.
-- ============================================================
BEGIN;

-- 1) Novas colunas no plantio (o plot da safra carrega seu proprio desenho)
ALTER TABLE tblplantio ADD COLUMN IF NOT EXISTS codfazenda integer NULL REFERENCES tblfazenda(codfazenda);
ALTER TABLE tblplantio ADD COLUMN IF NOT EXISTS talhao     varchar(60) NULL;        -- nome/numero do talhao nesta safra
ALTER TABLE tblplantio ADD COLUMN IF NOT EXISTS geometria  jsonb NULL;              -- GeoJSON Polygon (copia editavel)
ALTER TABLE tblplantio ADD COLUMN IF NOT EXISTS area       numeric(12,4) NULL;      -- ha do poligono
ALTER TABLE tblplantio ADD COLUMN IF NOT EXISTS cor        varchar(9) NULL;         -- #RRGGBB no mapa
ALTER TABLE tblplantio ADD COLUMN IF NOT EXISTS latitude   numeric NULL;            -- centro do poligono
ALTER TABLE tblplantio ADD COLUMN IF NOT EXISTS longitude  numeric NULL;

-- 2) Backfill: materializa no plantio o que hoje vem via talhao base.
--    (preserva o mapa ja desenhado nos plantios existentes)
UPDATE tblplantio p
   SET codfazenda = t.codfazenda,
       talhao     = COALESCE(p.talhao, t.talhao),
       geometria  = COALESCE(p.geometria, t.geometria),
       area       = COALESCE(p.area, t.area),
       cor        = COALESCE(p.cor, t.cor),
       latitude   = COALESCE(p.latitude, t.latitude),
       longitude  = COALESCE(p.longitude, t.longitude)
  FROM tbltalhao t
 WHERE t.codtalhao = p.codtalhao
   AND p.codfazenda IS NULL;

-- 3) codtalhao deixa de ser obrigatorio (vira so "clonado de qual talhao base").
--    A fonte de verdade do desenho passa a ser o proprio plantio.
ALTER TABLE tblplantio ALTER COLUMN codtalhao DROP NOT NULL;

-- 4) Identidade do plot por safra+fazenda+nome+variedade (so entre ativos).
--    Permite o mesmo talhao com 2 variedades (2 plantios, poligonos distintos);
--    barra so duplicata real (mesmo talhao + mesma variedade). Remove a antiga.
ALTER TABLE tblplantio DROP CONSTRAINT IF EXISTS tblplantio_codsafra_codtalhao_key;
DROP INDEX IF EXISTS uk_plantio_safra_fazenda_talhao;
CREATE UNIQUE INDEX uk_plantio_safra_fazenda_talhao
    ON tblplantio (codsafra, codfazenda, talhao, codvariedade)
 WHERE inativo IS NULL;

COMMIT;

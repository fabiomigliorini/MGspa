-- ============================================================
-- Agro: area do plantio num campo so.
--
-- O plantio tinha duas colunas de area que confundiam: `area` (ha do poligono
-- desenhado) e `areaplantada` (a area "oficial"). A tela passou a mostrar um
-- campo unico "Area plantada" (vem do desenho por padrao, editavel na mao), e
-- toda a matematica (KPIs, resumo por safra) ja usa `areaplantada`. Entao
-- removemos a coluna `area` do plantio.
--
-- (O tbltalhao continua com sua propria `area` — layout base da fazenda — que
--  nao tem nada a ver com esta.)
--
-- Rodar no dev e na PROD. PostgreSQL. Idempotente.
-- ============================================================
--
-- GUARDA: so roda se tblplantio.area existe (o UPDATE que le a coluna falhava sem ela).
-- Bloco unico (atomico); sem BEGIN/COMMIT pra nao deixar transacao aberta no DBeaver.
-- =====================================================================

SET lock_timeout = '5s';

DO $agro$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblplantio' AND column_name = 'area') THEN
    RAISE NOTICE '07 plantio_area_unica: pulado (tblplantio.area ja removida)';
    RETURN;
  END IF;

  -- Preserva o que houver: se a area plantada estiver vazia, herda a do poligono.
  UPDATE tblplantio
     SET areaplantada = area
   WHERE areaplantada IS NULL
     AND area IS NOT NULL;

  -- Remove a coluna redundante.
  ALTER TABLE tblplantio DROP COLUMN IF EXISTS area;

  RAISE NOTICE '07 plantio_area_unica: aplicado';
END
$agro$;

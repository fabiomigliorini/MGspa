-- Emoji por cultura (visual na lista / cabeçalho). Opcional; cai no ícone padrão se vazio.
--
-- GUARDA: pula se tblcultura.icone ja existe (o ADD COLUMN nao tinha IF NOT EXISTS).
-- Bloco unico (atomico); sem BEGIN/COMMIT pra nao deixar transacao aberta no DBeaver.
-- =====================================================================

SET lock_timeout = '5s';

DO $agro$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblcultura' AND column_name = 'icone') THEN
    RAISE NOTICE '06 cultura_icone: pulado (icone ja existe)';
    RETURN;
  END IF;

  ALTER TABLE tblcultura ADD COLUMN icone varchar(8);

  -- Sugestão de valores iniciais (ajuste os nomes conforme cadastro):
  UPDATE tblcultura SET icone = '🌽' WHERE lower(cultura) LIKE '%milho%' AND icone IS NULL;
  UPDATE tblcultura SET icone = '🫛' WHERE lower(cultura) LIKE '%soja%'  AND icone IS NULL;

  RAISE NOTICE '06 cultura_icone: aplicado';
END
$agro$;

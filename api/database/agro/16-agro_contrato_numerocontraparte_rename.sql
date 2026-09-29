-- =====================================================================
-- Renomeia tblcontrato.numerocomprador -> numerocontraparte.
-- "Contraparte" e mais generico (comprador na venda, vendedor na compra).
-- Reaplicavel (so renomeia se a coluna antiga ainda existir).
-- =====================================================================
--
-- GUARDA: so roda se numerocomprador ainda existe (o RENAME puro falhava na 2a
-- rodada). Se o passo 12 ja criou numerocontraparte, copia e dropa a antiga.
-- Bloco unico (atomico); sem BEGIN/COMMIT pra nao deixar transacao aberta no DBeaver.
-- =====================================================================

SET lock_timeout = '5s';

DO $agro$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblcontrato' AND column_name = 'numerocomprador') THEN
    RAISE NOTICE '16 numerocontraparte_rename: pulado (numerocomprador ja nao existe)';
    RETURN;
  END IF;

  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblcontrato' AND column_name = 'numerocontraparte') THEN
    -- O passo 12 atual ja cria numerocontraparte: junta o que houver e descarta a antiga.
    UPDATE tblcontrato SET numerocontraparte = numerocomprador
     WHERE numerocontraparte IS NULL AND numerocomprador IS NOT NULL;
    ALTER TABLE tblcontrato DROP COLUMN numerocomprador;
  ELSE
    ALTER TABLE tblcontrato
      RENAME COLUMN numerocomprador TO numerocontraparte;
  END IF;

  RAISE NOTICE '16 numerocontraparte_rename: aplicado';
END
$agro$;

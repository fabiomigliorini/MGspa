-- =====================================================================
-- Exclusao logica de dispositivo PDV (tela /config/pdv do app Negocios).
-- tblpdv e referenciada por 6 tabelas filhas sem ON DELETE CASCADE
-- (tblnegocio, tblliquidacaotitulo, tblpagarmepagamento, tblpagarmepedido,
-- tblpix, tblpixcob), entao "excluir" apenas oculta o registro da listagem
-- em vez de apagar. NULL = visivel, mesma semantica da coluna 'inativo'.
-- Se o dispositivo voltar a se registrar (PdvService::dispositivo), a coluna
-- e limpa automaticamente e ele reaparece como Nao Autorizado.
-- Reaplicavel (ADD COLUMN IF NOT EXISTS).
-- =====================================================================

BEGIN;

ALTER TABLE tblpdv
  ADD COLUMN IF NOT EXISTS excluido timestamp NULL;

COMMENT ON COLUMN tblpdv.excluido IS
  'Exclusao logica: dispositivo oculto da listagem de /config/pdv. NULL = visivel. Limpo automaticamente se o dispositivo voltar a se registrar.';

COMMIT;

-- =====================================================================
-- TASK-255 — Remove do tblusuario as impressoras do usuario, sem uso (vazias
-- em todos os usuarios ativos): impressoratermica, impressoramatricial,
-- impressoratelanegocio. A impressora e' do dispositivo (tblpdv) e, na
-- sequencia, de um cadastro proprio (tblimpressora).
--
-- ORDEM: depois da publicacao do codigo que deixou de ler essas colunas:
--   - api  (Usuario, UsuarioController::store, NFePHPService::imprimir)
--   - MGLara
--   - MGsis (Usuario, UserIdentity: sem isso o login do MGsis para)
--
-- Reaplicavel (IF EXISTS). Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

ALTER TABLE tblusuario
    DROP COLUMN IF EXISTS impressoratermica,
    DROP COLUMN IF EXISTS impressoramatricial,
    DROP COLUMN IF EXISTS impressoratelanegocio;

COMMIT;

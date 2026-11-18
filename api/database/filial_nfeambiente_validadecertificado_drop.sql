-- =====================================================================
-- Remove duas colunas do tblfilial (TASK-278):
--
--   nfeambiente          O ambiente da SEFAZ e' do servidor, nao da filial:
--                        o api le NFE_AMBIENTE do .env e o MGsis le
--                        define('NFE_AMBIENTE', ...) do protected/.env.php.
--                        Sem a variavel, ou com outro valor, e' homologacao.
--   validadecertificado  Ninguem mais le; a validade real vem do .pfx
--                        (CertificadoService::validarValidade).
--
-- ANTES DO DEPLOY (o api e o MGsis param de ler a coluna na publicacao):
--   1. conferir se alguma filial esta fora de producao de proposito:
--        select codfilial, filial, nfeambiente from tblfilial where nfeambiente <> 1;
--      depois do deploy ela passa a emitir no ambiente do servidor.
--   2. NFE_AMBIENTE=1 no .env do api (e php artisan config:cache, se usado).
--   3. define('NFE_AMBIENTE', 1); no protected/.env.php do MGsis.
--   Sem os passos 2 e 3, producao emite em homologacao (nota sem valor fiscal).
--
-- ORDEM: logo depois da publicacao do api, do pessoas, do MGsis e do MGLara.
-- O codigo anterior le a coluna (o MGsis exige nfeambiente no model Filial);
-- derrubar antes do deploy quebra a emissao e o NFe de Terceiros.
--
-- Reaplicavel (IF EXISTS). Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

ALTER TABLE tblfilial DROP COLUMN IF EXISTS nfeambiente;
ALTER TABLE tblfilial DROP COLUMN IF EXISTS validadecertificado;

COMMIT;

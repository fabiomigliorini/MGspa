-- =====================================================================
-- Remove do tblpdv o que saiu dele na TASK-46:
--   autorizado — autorizado = ativo (inativo nulo). O dispositivo nasce
--                inativo e ativar e' o que o autoriza.
--   ip, latitude, longitude, precisao — moram no historico
--                tblpdvlocalizacao (criado no pdv_configuracao.sql).
--
-- ORDEM: depois do pdv_configuracao.sql e da publicacao do codigo. O codigo
-- anterior le o autorizado (PdvService::podeAcessar) e grava ip e
-- localizacao na sincronizacao; derrubar antes do deploy quebra o acesso
-- de todos os PDVs. As sincronizacoes entre o pdv_configuracao.sql e o
-- deploy ficam fora do historico; a proxima grava de novo.
--
-- Antes do DROP repete a passagem dos que esperavam autorizacao para
-- inativo (algum pode ter se registrado entre o pdv_configuracao.sql e o
-- deploy).
--
-- Reaplicavel (guarda pela coluna). Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'tblpdv' AND column_name = 'autorizado'
    ) THEN
        UPDATE tblpdv SET inativo = coalesce(criacao, now())
        WHERE NOT autorizado AND inativo IS NULL;
    END IF;
END $$;

ALTER TABLE tblpdv DROP COLUMN IF EXISTS autorizado;
ALTER TABLE tblpdv
    DROP COLUMN IF EXISTS ip,
    DROP COLUMN IF EXISTS latitude,
    DROP COLUMN IF EXISTS longitude,
    DROP COLUMN IF EXISTS precisao;

COMMIT;

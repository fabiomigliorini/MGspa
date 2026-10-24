-- =====================================================================
-- Remove o tblpdv.codfilial (TASK-257): a filial do PDV e' a do local de
-- estoque dele (tblestoquelocal.codfilial). Desde a TASK-46 o codfilial
-- era copia, refeita a cada edicao do dispositivo (PdvService::update).
--
-- Antes do DROP:
--   1. quem tem filial e nao tem local recebe o local da filial (hoje e'
--      um local por filial; havendo mais, o ativo de menor codigo);
--   2. avisa (NOTICE) os PDVs cuja filial nao bate com a do local: depois
--      do DROP a filial deles passa a ser a do local.
--
-- ORDEM: logo depois da publicacao do codigo. O codigo anterior grava o
-- codfilial (PdvService::update) e le a relacao Filial do Pdv; derrubar
-- antes do deploy quebra a edicao e a listagem dos dispositivos. Entre o
-- deploy e este script, o PDV sem local fica sem filial (o Gerente nao o
-- ve na lista de dispositivos).
--
-- Reaplicavel (guarda pela coluna). Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

DO $$
DECLARE
    reg record;
    qtd integer;
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'tblpdv' AND column_name = 'codfilial'
    ) THEN
        RETURN;
    END IF;

    UPDATE tblpdv p SET codestoquelocal = (
        SELECT el.codestoquelocal FROM tblestoquelocal el
        WHERE el.codfilial = p.codfilial
        ORDER BY el.inativo IS NOT NULL, el.codestoquelocal
        LIMIT 1
    )
    WHERE p.codestoquelocal IS NULL AND p.codfilial IS NOT NULL;
    GET DIAGNOSTICS qtd = ROW_COUNT;
    RAISE NOTICE 'PDVs que receberam o local de estoque da filial: %', qtd;

    FOR reg IN
        SELECT p.codpdv, p.apelido, p.codfilial AS antiga, el.codfilial AS nova
        FROM tblpdv p
        INNER JOIN tblestoquelocal el ON (el.codestoquelocal = p.codestoquelocal)
        WHERE p.codfilial IS NOT NULL AND p.codfilial <> el.codfilial
        ORDER BY p.codpdv
    LOOP
        RAISE NOTICE 'PDV % (%) passa da filial % para a % (a do local de estoque)',
            reg.codpdv, reg.apelido, reg.antiga, reg.nova;
    END LOOP;
END $$;

ALTER TABLE tblpdv DROP CONSTRAINT IF EXISTS fk_tblpdv_tblfilial;
ALTER TABLE tblpdv DROP COLUMN IF EXISTS codfilial;

COMMIT;

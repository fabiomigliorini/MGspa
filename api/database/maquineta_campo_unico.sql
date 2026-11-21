-- =====================================================================
-- TASK-264: a maquineta e' o cadastro unico; a operadora guarda so' o que e' dela.
--
-- Regra: o que e' comum a todas as maquinetas e 1:1 fica na tblmaquineta; o que e'
-- especifico da operadora ou N:1 fica na tabela da operadora.
--
--   tblmaquineta     apelido, serial, codfilial, compartilhada, codpessoa, integracao,
--                    codpagarmepos, inativo; codsauruspinpad sai, entra codsauruspdv
--                    (o PDV Saurus; unico; nulo ate' o primeiro Parear)
--   tblsauruspdv     credencial na Saurus (id, numero, chaves, contrato);
--                    saem apelido, codfilial, inativo
--   tblsauruspinpad  historico dos aparelhos do PDV Saurus (id da Saurus, codsauruspdv, inativo =
--                    aparelho substituido); saem apelido, serial, codfilial, observacoes
--   tblpagarmepos    so' o codigo (ancora de pedido e pagamento PagarMe);
--                    saem apelido, serial, codfilial, inativo
--
-- Tambem junta o lixo da migracao maquineta.sql (TASK-188), que criou uma maquineta por
-- pinpad: as maquinetas do mesmo PDV Saurus vao para a ativa (pagamentos, periodos e maquineta
-- padrao do PDV) e as outras sao excluidas.
--
-- Go-live: junto com o deploy (o codigo novo nao le as colunas que saem).
-- Idempotente. Transacional.
-- =====================================================================

\set ON_ERROR_STOP on

BEGIN;

ALTER TABLE tblmaquineta ADD COLUMN IF NOT EXISTS codsauruspdv bigint;

DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.columns
               WHERE table_name = 'tblmaquineta' AND column_name = 'codsauruspinpad') THEN

        -- maquineta que fica em cada PDV Saurus: a ativa; sem ativa, a do pinpad mais novo
        CREATE TEMP TABLE tmp_junta ON COMMIT DROP AS
        SELECT m.codmaquineta AS errada, c.codmaquineta AS certa
        FROM tblmaquineta m
        JOIN tblsauruspinpad pin ON pin.codsauruspinpad = m.codsauruspinpad
        JOIN LATERAL (
            SELECT m2.codmaquineta
            FROM tblmaquineta m2
            JOIN tblsauruspinpad pin2 ON pin2.codsauruspinpad = m2.codsauruspinpad
            WHERE pin2.codsauruspdv = pin.codsauruspdv
            ORDER BY (m2.inativo IS NULL) DESC, pin2.codsauruspinpad DESC
            LIMIT 1
        ) c ON TRUE
        WHERE c.codmaquineta <> m.codmaquineta;

        UPDATE tblpagamento p SET codmaquineta = j.certa
        FROM tmp_junta j WHERE p.codmaquineta = j.errada;

        UPDATE tblmaquinetalote l SET codmaquineta = j.certa
        FROM tmp_junta j WHERE l.codmaquineta = j.errada;

        UPDATE tblpdv x SET codmaquineta = j.certa
        FROM tmp_junta j WHERE x.codmaquineta = j.errada;

        DELETE FROM tblmaquineta m USING tmp_junta j WHERE m.codmaquineta = j.errada;

        -- a maquineta aponta para o PDV Saurus do pinpad
        UPDATE tblmaquineta m SET codsauruspdv = pin.codsauruspdv
        FROM tblsauruspinpad pin
        WHERE pin.codsauruspinpad = m.codsauruspinpad
          AND m.codsauruspdv IS NULL;

        ALTER TABLE tblmaquineta DROP CONSTRAINT IF EXISTS tblmaquineta_integracao_check;
        ALTER TABLE tblmaquineta DROP CONSTRAINT IF EXISTS fk_tblmaquineta_tblsauruspinpad;
        DROP INDEX IF EXISTS uk_tblmaquineta_codsauruspinpad;
        ALTER TABLE tblmaquineta DROP COLUMN codsauruspinpad;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_tblmaquineta_tblsauruspdv') THEN
        ALTER TABLE tblmaquineta ADD CONSTRAINT fk_tblmaquineta_tblsauruspdv
            FOREIGN KEY (codsauruspdv) REFERENCES tblsauruspdv (codsauruspdv) ON UPDATE CASCADE;
    END IF;

    -- manual: sem integracao; P: so' POS PagarMe; S: PDV Saurus (nulo ate' o primeiro Parear)
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'tblmaquineta_integracao_check') THEN
        ALTER TABLE tblmaquineta ADD CONSTRAINT tblmaquineta_integracao_check CHECK (
            (integracao IS NULL AND codpagarmepos IS NULL AND codsauruspdv IS NULL)
            OR (integracao = 'P' AND codpagarmepos IS NOT NULL AND codsauruspdv IS NULL)
            OR (integracao = 'S' AND codpagarmepos IS NULL)
        );
    END IF;
END $$;

CREATE UNIQUE INDEX IF NOT EXISTS uk_tblmaquineta_codsauruspdv ON tblmaquineta (codsauruspdv);

-- pinpad ativo = aparelho atual do PDV Saurus. Substituido quando outro foi pareado no mesmo
-- PDV Saurus depois, ou o mesmo aparelho em outro: inativo desde o pareamento do substituto.
UPDATE tblsauruspinpad pin SET inativo = x.substituido
FROM (
    SELECT p.codsauruspinpad,
        (SELECT min(o.criacao) FROM tblsauruspinpad o
         WHERE o.codsauruspinpad > p.codsauruspinpad
           AND (o.codsauruspdv = p.codsauruspdv OR o.id = p.id)) AS substituido
    FROM tblsauruspinpad p
) x
WHERE x.codsauruspinpad = pin.codsauruspinpad
  AND pin.inativo IS DISTINCT FROM x.substituido;

-- o mesmo aparelho uma vez so' em cada PDV Saurus (o Parear reativa o registro que ja' existe)
CREATE UNIQUE INDEX IF NOT EXISTS uk_tblsauruspinpad_id_codsauruspdv ON tblsauruspinpad (id, codsauruspdv);

-- copias do cadastro da maquineta (divergencia de filial e serial conferida: zero)
ALTER TABLE tblsauruspdv
    DROP COLUMN IF EXISTS apelido,
    DROP COLUMN IF EXISTS codfilial,
    DROP COLUMN IF EXISTS inativo;

ALTER TABLE tblsauruspinpad
    DROP COLUMN IF EXISTS apelido,
    DROP COLUMN IF EXISTS serial,
    DROP COLUMN IF EXISTS codfilial,
    DROP COLUMN IF EXISTS observacoes;

ALTER TABLE tblpagarmepos
    DROP COLUMN IF EXISTS apelido,
    DROP COLUMN IF EXISTS serial,
    DROP COLUMN IF EXISTS codfilial,
    DROP COLUMN IF EXISTS inativo;

COMMIT;

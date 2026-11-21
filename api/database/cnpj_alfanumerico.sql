-- =====================================================================
-- CNPJ alfanumerico (TASK-283).
--
-- Desde jul/2026 a Receita emite CNPJ com letras: 14 caracteres, os 12
-- primeiros de A-Z/0-9 e os 2 digitos verificadores numericos. As colunas
-- que guardavam CNPJ como numero passam a texto: varchar(14), maiusculo,
-- zeros a esquerda, cada um no seu tamanho (CPF com 11, CNPJ com 14).
--
--   tblpessoa.cnpj                  CPF ou CNPJ, pelo fisica
--   tblpessoaconta.cnpj             CPF ou CNPJ (sem fisica: regra do DV)
--   tblchequeemitente.cnpj          idem
--   tblpagamento.chequecnpj         idem
--   tblnfeterceiro.cnpj             idem
--   tblnfeterceiropagamento.cnpj    idem
--   tblpessoaconta.pixcnpj          so CNPJ (o CPF fica no pixcpf)
--   tbldistribuicaodfeevento.cnpj   so CNPJ (o CPF fica no cpf)
--   tblpix.cnpj                     so CNPJ (o CPF fica no cpf)
--   tblpixcob.cnpj                  so CNPJ (o CPF fica no cpf)
--
-- Regra do DV (colunas que misturam CPF e CNPJ sem o fisica): vira CPF (11)
-- quando cabe em 11 digitos e o digito verificador de CPF bate; senao CNPJ
-- (14). E' a mesma regra do cnpjCpfLimpo() do MgHelpers.
--
-- As colunas so de CPF (tblnegocio.cpf, tblnotafiscal.cpf, tblpix.cpf,
-- tblpixcob.cpf, tbldistribuicaodfeevento.cpf, tblpessoaconta.pixcpf)
-- ficam como estao.
--
-- Zero, que no numero significava "sem documento", vira NULL (e o
-- tblchequeemitente.cnpj deixa de ser NOT NULL por isso).
--
-- As views que usam essas colunas sao derrubadas e recriadas com a mesma
-- definicao e os mesmos GRANTs. A tblnegocioformapagamento (view de UNION)
-- troca o NULL::numeric(14,0) do chequecnpj por NULL::varchar(14).
--
-- Rodar JUNTO com o deploy do api, do MGsis e do MGLara: o codigo antigo
-- usa to_char(cnpj, ...), que nao existe para texto.
--
-- Idempotente (coluna que ja e' texto fica como esta). Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '600s';

CREATE FUNCTION pg_temp.cpf_valido(p_cpf text) RETURNS boolean
LANGUAGE plpgsql IMMUTABLE AS $$
DECLARE
    v_t int;
    v_c int;
    v_d int;
BEGIN
    IF p_cpf IS NULL OR p_cpf !~ '^[0-9]{11}$' OR p_cpf ~ '^(.)\1{10}$' THEN
        RETURN false;
    END IF;
    FOR v_t IN 9..10 LOOP
        v_d := 0;
        FOR v_c IN 0..(v_t - 1) LOOP
            v_d := v_d + substr(p_cpf, v_c + 1, 1)::int * ((v_t + 1) - v_c);
        END LOOP;
        v_d := ((10 * v_d) % 11) % 10;
        IF substr(p_cpf, v_t + 1, 1)::int <> v_d THEN
            RETURN false;
        END IF;
    END LOOP;
    RETURN true;
END;
$$;

-- CPF (11) ou CNPJ (14) a partir do numero antigo, pela regra do DV
CREATE FUNCTION pg_temp.cnpj_cpf(p_numero numeric) RETURNS varchar
LANGUAGE sql IMMUTABLE AS $$
    SELECT CASE
        WHEN p_numero IS NULL THEN NULL
        WHEN p_numero <= 99999999999
         AND pg_temp.cpf_valido(lpad(p_numero::text, 11, '0')) THEN lpad(p_numero::text, 11, '0')
        ELSE lpad(p_numero::text, 14, '0')
    END
$$;

DO $$
DECLARE
    v_views text[] := ARRAY[
        'vwpessoa', 'vwnegocio', 'vwnegocio_listagem', 'vwnotafiscal', 'tblnegocioformapagamento'
    ];
    v_view text;
    v_def text;
    v_grant record;
BEGIN
    IF (
        SELECT data_type
        FROM information_schema.columns
        WHERE table_schema = current_schema()
          AND table_name = 'tblpessoa'
          AND column_name = 'cnpj'
    ) = 'character varying' THEN
        RAISE NOTICE 'tblpessoa.cnpj ja e texto, nada a fazer';
        RETURN;
    END IF;

    -- Guarda definicao e GRANTs das views
    CREATE TEMP TABLE tmp_view_cnpj (ordem int, nome text, def text) ON COMMIT DROP;
    CREATE TEMP TABLE tmp_grant_cnpj (nome text, grantee text, privilegio text) ON COMMIT DROP;
    FOR i IN 1..array_length(v_views, 1) LOOP
        v_view := v_views[i];
        INSERT INTO tmp_view_cnpj VALUES (i, v_view, pg_get_viewdef(v_view::regclass, true));
        INSERT INTO tmp_grant_cnpj
        SELECT v_view,
               CASE WHEN a.grantee = 0 THEN 'public' ELSE quote_ident(pg_get_userbyid(a.grantee)) END,
               a.privilege_type
        FROM pg_class c, aclexplode(c.relacl) a
        WHERE c.oid = v_view::regclass
          AND a.grantee <> c.relowner;
    END LOOP;

    UPDATE tmp_view_cnpj
    SET def = replace(def, 'NULL::numeric(14,0) AS chequecnpj', 'NULL::character varying(14) AS chequecnpj')
    WHERE nome = 'tblnegocioformapagamento';

    FOR i IN REVERSE array_length(v_views, 1)..1 LOOP
        EXECUTE format('DROP VIEW %I', v_views[i]);
    END LOOP;

    ALTER TABLE tblpessoa
        ALTER COLUMN cnpj TYPE varchar(14)
        USING lpad(cnpj::text, CASE WHEN fisica THEN 11 ELSE 14 END, '0');

    ALTER TABLE tblpessoaconta
        ALTER COLUMN cnpj TYPE varchar(14) USING pg_temp.cnpj_cpf(cnpj),
        ALTER COLUMN pixcnpj TYPE varchar(14) USING lpad(pixcnpj::text, 14, '0');

    ALTER TABLE tblchequeemitente
        ALTER COLUMN cnpj TYPE varchar(14) USING pg_temp.cnpj_cpf(cnpj);

    ALTER TABLE tblpagamento
        ALTER COLUMN chequecnpj TYPE varchar(14) USING pg_temp.cnpj_cpf(chequecnpj);

    ALTER TABLE tblnfeterceiro
        ALTER COLUMN cnpj TYPE varchar(14) USING pg_temp.cnpj_cpf(cnpj);

    ALTER TABLE tblnfeterceiropagamento
        ALTER COLUMN cnpj TYPE varchar(14) USING pg_temp.cnpj_cpf(cnpj);

    ALTER TABLE tbldistribuicaodfeevento
        ALTER COLUMN cnpj TYPE varchar(14) USING lpad(cnpj::text, 14, '0');

    ALTER TABLE tblpix
        ALTER COLUMN cnpj TYPE varchar(14) USING lpad(cnpj::text, 14, '0');

    ALTER TABLE tblpixcob
        ALTER COLUMN cnpj TYPE varchar(14) USING lpad(cnpj::text, 14, '0');

    -- Recria as views, na ordem original, com os mesmos GRANTs
    FOR v_view, v_def IN SELECT nome, def FROM tmp_view_cnpj ORDER BY ordem LOOP
        EXECUTE format('CREATE VIEW %I AS %s', v_view, v_def);
        FOR v_grant IN SELECT grantee, privilegio FROM tmp_grant_cnpj WHERE nome = v_view LOOP
            EXECUTE format('GRANT %s ON %I TO %s', v_grant.privilegio, v_view, v_grant.grantee);
        END LOOP;
    END LOOP;
END;
$$;

-- Zero no numero era "sem documento" (4.934 emitentes de cheque gravados pelo MGsis/MGLara
-- e uma pessoa); em texto viraria '000...', que conta como preenchido. Vira NULL, e o
-- emitente de cheque deixa de exigir o documento (o api ja nao grava emitente sem ele).
ALTER TABLE tblchequeemitente ALTER COLUMN cnpj DROP NOT NULL;
UPDATE tblpessoa SET cnpj = NULL WHERE cnpj ~ '^0+$';
UPDATE tblpessoaconta SET cnpj = NULL WHERE cnpj ~ '^0+$';
UPDATE tblpessoaconta SET pixcnpj = NULL WHERE pixcnpj ~ '^0+$';
UPDATE tblchequeemitente SET cnpj = NULL WHERE cnpj ~ '^0+$';
UPDATE tblpagamento SET chequecnpj = NULL WHERE chequecnpj ~ '^0+$';
UPDATE tblnfeterceiro SET cnpj = NULL WHERE cnpj ~ '^0+$';
UPDATE tblnfeterceiropagamento SET cnpj = NULL WHERE cnpj ~ '^0+$';
UPDATE tbldistribuicaodfeevento SET cnpj = NULL WHERE cnpj ~ '^0+$';
UPDATE tblpix SET cnpj = NULL WHERE cnpj ~ '^0+$';
UPDATE tblpixcob SET cnpj = NULL WHERE cnpj ~ '^0+$';

COMMIT;

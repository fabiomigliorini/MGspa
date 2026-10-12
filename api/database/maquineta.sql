-- =====================================================================
-- Cadastro unico de maquinetas (M3 do plano doc-3, fechamento de caixa).
--
--   tblmaquineta: todo terminal de cartao, integrado ou nao.
--     integracao  nulo = manual | P = PagarMe (Stone) | S = Saurus (SafraPay)
--     codpessoa   adquirente (Stone, Safra, Brasil Card, Le Card, MultVale, Cielo)
--     compartilhada  aparece no PDV de todas as filiais (acesso de site
--                    feito numa filial e usado por todas)
--     codpagarmepos / codsauruspinpad  configuracao da integracao (uma
--                    maquineta por POS PagarMe e por pinpad Saurus)
--
--   tblnegocioformapagamento.codmaquineta: maquineta do pagamento em cartao.
--
-- Carga inicial:
--   - todos os POS PagarMe e pinpads Saurus (os inativos entram inativos;
--     pinpad substituido por outro no mesmo PDV Saurus entra inativo);
--   - maquineta de site de Brasil Card, Le Card e MultVale (filial 101,
--     compartilhada);
--   - serial digitado no cartao manual: casa por serial + filial do negocio;
--     se nao achar, vira maquineta manual daquela filial;
--   - historico sem aparelho: "Historico Stone/SafraPay/Cielo Lio" inativas
--     por filial; manual de Brasil Card/Le Card/MultVale aponta para o site;
--   - pessoa Cielo S.A. (adquirente do Cielo Lio, 2020-2021).
--
-- Ficam sem maquineta so os cartoes manuais antigos sem parceiro.
-- Idempotente. Transacional. Pode rodar de novo a vontade.
-- =====================================================================

\set ON_ERROR_STOP on

-- ---------------------------------------------------------------------
-- 1. Estrutura
-- ---------------------------------------------------------------------
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '10min';

CREATE TABLE IF NOT EXISTS tblmaquineta (
    codmaquineta bigserial NOT NULL,
    apelido varchar(50) NOT NULL,
    serial varchar(50),
    codfilial bigint NOT NULL,
    compartilhada boolean NOT NULL DEFAULT false,
    codpessoa bigint NOT NULL,
    integracao char(1),
    codpagarmepos bigint,
    codsauruspinpad bigint,
    inativo timestamp(0) without time zone,
    criacao timestamp(0) without time zone DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) without time zone DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT pk_tblmaquineta PRIMARY KEY (codmaquineta)
);

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_tblmaquineta_tblfilial') THEN
        ALTER TABLE tblmaquineta ADD CONSTRAINT fk_tblmaquineta_tblfilial
            FOREIGN KEY (codfilial) REFERENCES tblfilial (codfilial) ON UPDATE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_tblmaquineta_tblpessoa') THEN
        ALTER TABLE tblmaquineta ADD CONSTRAINT fk_tblmaquineta_tblpessoa
            FOREIGN KEY (codpessoa) REFERENCES tblpessoa (codpessoa) ON UPDATE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_tblmaquineta_tblpagarmepos') THEN
        ALTER TABLE tblmaquineta ADD CONSTRAINT fk_tblmaquineta_tblpagarmepos
            FOREIGN KEY (codpagarmepos) REFERENCES tblpagarmepos (codpagarmepos) ON UPDATE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_tblmaquineta_tblsauruspinpad') THEN
        ALTER TABLE tblmaquineta ADD CONSTRAINT fk_tblmaquineta_tblsauruspinpad
            FOREIGN KEY (codsauruspinpad) REFERENCES tblsauruspinpad (codsauruspinpad) ON UPDATE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_tblmaquineta_tblusuario') THEN
        ALTER TABLE tblmaquineta ADD CONSTRAINT fk_tblmaquineta_tblusuario
            FOREIGN KEY (codusuariocriacao) REFERENCES tblusuario (codusuario) ON UPDATE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_tblmaquineta_tblusuario_0') THEN
        ALTER TABLE tblmaquineta ADD CONSTRAINT fk_tblmaquineta_tblusuario_0
            FOREIGN KEY (codusuarioalteracao) REFERENCES tblusuario (codusuario) ON UPDATE CASCADE;
    END IF;
    -- manual: sem integracao; P: so POS PagarMe; S: so pinpad Saurus
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'tblmaquineta_integracao_check') THEN
        ALTER TABLE tblmaquineta ADD CONSTRAINT tblmaquineta_integracao_check CHECK (
            (integracao IS NULL AND codpagarmepos IS NULL AND codsauruspinpad IS NULL)
            OR (integracao = 'P' AND codpagarmepos IS NOT NULL AND codsauruspinpad IS NULL)
            OR (integracao = 'S' AND codsauruspinpad IS NOT NULL AND codpagarmepos IS NULL)
        );
    END IF;
END $$;

CREATE UNIQUE INDEX IF NOT EXISTS uk_tblmaquineta_codpagarmepos ON tblmaquineta (codpagarmepos);
CREATE UNIQUE INDEX IF NOT EXISTS uk_tblmaquineta_codsauruspinpad ON tblmaquineta (codsauruspinpad);
CREATE INDEX IF NOT EXISTS idx_tblmaquineta_codfilial ON tblmaquineta (codfilial);
CREATE INDEX IF NOT EXISTS idx_tblmaquineta_serial ON tblmaquineta (serial);

ALTER TABLE tblnegocioformapagamento ADD COLUMN IF NOT EXISTS codmaquineta bigint;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_tblnegocioformapagamento_tblmaquineta') THEN
        ALTER TABLE tblnegocioformapagamento ADD CONSTRAINT fk_tblnegocioformapagamento_tblmaquineta
            FOREIGN KEY (codmaquineta) REFERENCES tblmaquineta (codmaquineta) ON UPDATE CASCADE;
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_tblnegocioformapagamento_codmaquineta
    ON tblnegocioformapagamento (codmaquineta);

COMMIT;

-- ---------------------------------------------------------------------
-- 2. Carga inicial e backfill
-- ---------------------------------------------------------------------
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '30min';

-- Cielo S.A., adquirente do Cielo Lio
INSERT INTO tblpessoa (pessoa, fantasia, cnpj, fisica, notafiscal)
SELECT 'Cielo S.A. - Instituição de Pagamento', 'Cielo', 1027058000191, false, 0
WHERE NOT EXISTS (SELECT 1 FROM tblpessoa WHERE cnpj = 1027058000191);

-- POS PagarMe (Stone), todos; inativo como esta
INSERT INTO tblmaquineta (apelido, serial, codfilial, codpessoa, integracao, codpagarmepos, inativo, criacao)
SELECT pos.apelido, pos.serial, pos.codfilial, 9993, 'P', pos.codpagarmepos, pos.inativo, pos.criacao
FROM tblpagarmepos pos
WHERE NOT EXISTS (SELECT 1 FROM tblmaquineta m WHERE m.codpagarmepos = pos.codpagarmepos)
ORDER BY pos.codpagarmepos;

-- Pinpads Saurus (SafraPay), todos. Apelido do PDV Saurus (o do pinpad esta
-- desatualizado). Pinpad que ja foi substituido por outro no mesmo PDV Saurus
-- entra inativo desde a criacao do substituto.
INSERT INTO tblmaquineta (apelido, serial, codfilial, codpessoa, integracao, codsauruspinpad, inativo, criacao)
SELECT
    COALESCE(pdv.apelido, pin.apelido, 'Pinpad ' || pin.codsauruspinpad),
    pin.serial,
    COALESCE(pdv.codfilial, pin.codfilial),
    20119,
    'S',
    pin.codsauruspinpad,
    COALESCE(
        pin.inativo,
        (SELECT min(nov.criacao) FROM tblsauruspinpad nov
         WHERE nov.codsauruspdv = pin.codsauruspdv AND nov.codsauruspinpad > pin.codsauruspinpad),
        pdv.inativo
    ),
    pin.criacao
FROM tblsauruspinpad pin
LEFT JOIN tblsauruspdv pdv ON (pdv.codsauruspdv = pin.codsauruspdv)
WHERE NOT EXISTS (SELECT 1 FROM tblmaquineta m WHERE m.codsauruspinpad = pin.codsauruspinpad)
ORDER BY pin.codsauruspinpad;

-- Site de Brasil Card, Le Card e MultVale: um acesso na 101, usado por todas
INSERT INTO tblmaquineta (apelido, codfilial, compartilhada, codpessoa)
SELECT s.apelido, 101, true, s.codpessoa
FROM (VALUES
    ('Brasil Card Site', 15319),
    ('Le Card Site', 15320),
    ('MultVale Site', 10000650)
) AS s (apelido, codpessoa)
WHERE NOT EXISTS (
    SELECT 1 FROM tblmaquineta m
    WHERE m.codpessoa = s.codpessoa AND m.compartilhada AND m.integracao IS NULL
);

-- Serial digitado no cartao manual sem maquineta: casa por serial + filial
-- (ativa primeiro, depois a mais nova); o que nao casar vira maquineta
-- manual daquela filial, com a adquirente mais usada naquele serial.
CREATE TEMP TABLE _serial ON COMMIT DROP AS
SELECT nfp.codnegocioformapagamento, nfp.serialmaquineta AS serial, n.codfilial, nfp.codpessoa
FROM tblnegocioformapagamento nfp
JOIN tblnegocio n ON (n.codnegocio = nfp.codnegocio)
WHERE nfp.codmaquineta IS NULL
  AND nfp.integracao IS NOT TRUE
  AND nfp.serialmaquineta IS NOT NULL
  AND trim(nfp.serialmaquineta) <> '';

INSERT INTO tblmaquineta (apelido, serial, codfilial, codpessoa)
SELECT DISTINCT ON (s.serial, s.codfilial) left(s.serial, 50), s.serial, s.codfilial, s.codpessoa
FROM (
    SELECT serial, codfilial, codpessoa, count(*) AS qtd
    FROM _serial
    WHERE codpessoa IS NOT NULL
    GROUP BY serial, codfilial, codpessoa
) s
WHERE NOT EXISTS (
    SELECT 1 FROM tblmaquineta m WHERE m.serial = s.serial AND m.codfilial = s.codfilial
)
ORDER BY s.serial, s.codfilial, s.qtd DESC, s.codpessoa;

UPDATE tblnegocioformapagamento nfp
SET codmaquineta = (
    SELECT m.codmaquineta FROM tblmaquineta m
    WHERE m.serial = s.serial AND m.codfilial = s.codfilial
    ORDER BY (m.inativo IS NULL) DESC, m.codmaquineta DESC
    LIMIT 1
)
FROM _serial s
WHERE nfp.codnegocioformapagamento = s.codnegocioformapagamento;

-- PagarMe: POS que cobrou (pagamento nao cancelado mais recente) ou o do pedido
UPDATE tblnegocioformapagamento nfp
SET codmaquineta = m.codmaquineta
FROM (
    SELECT DISTINCT ON (pe.codpagarmepedido)
        pe.codpagarmepedido,
        COALESCE(pg.codpagarmepos, pe.codpagarmepos) AS codpagarmepos
    FROM tblpagarmepedido pe
    LEFT JOIN tblpagarmepagamento pg
        ON (pg.codpagarmepedido = pe.codpagarmepedido AND pg.codpagarmepos IS NOT NULL)
    ORDER BY pe.codpagarmepedido, (pg.valorcancelamento IS NULL) DESC, pg.codpagarmepagamento DESC
) ped
JOIN tblmaquineta m ON (m.codpagarmepos = ped.codpagarmepos)
WHERE nfp.codpagarmepedido = ped.codpagarmepedido
  AND nfp.codmaquineta IS NULL;

-- Saurus: pinpad do pagamento
UPDATE tblnegocioformapagamento nfp
SET codmaquineta = m.codmaquineta
FROM (
    SELECT DISTINCT ON (codsauruspedido) codsauruspedido, codsauruspinpad
    FROM tblsauruspagamento
    WHERE codsauruspinpad IS NOT NULL
    ORDER BY codsauruspedido, codsauruspagamento DESC
) pag
JOIN tblmaquineta m ON (m.codsauruspinpad = pag.codsauruspinpad)
WHERE nfp.codsauruspedido = pag.codsauruspedido
  AND nfp.codmaquineta IS NULL;

-- Historico sem aparelho identificavel
CREATE TEMP TABLE _historico ON COMMIT DROP AS
SELECT
    nfp.codnegocioformapagamento,
    n.codfilial,
    CASE
        WHEN nfp.codformapagamento = 5603 OR nfp.codliopedido IS NOT NULL THEN 'C'
        WHEN nfp.codformapagamento = 5605 THEN 'S'
        WHEN nfp.codpessoa = 9993 THEN 'S'
        WHEN nfp.codpessoa = 20119 THEN 'F'
        WHEN nfp.codpessoa IN (15319, 15320, 10000650) THEN 'W'
    END AS grupo,
    nfp.codpessoa
FROM tblnegocioformapagamento nfp
JOIN tblnegocio n ON (n.codnegocio = nfp.codnegocio)
WHERE nfp.codmaquineta IS NULL
  AND (
      nfp.codformapagamento IN (5603, 5605)
      OR nfp.codliopedido IS NOT NULL
      OR (nfp.codformapagamento = 2010
          AND nfp.codpessoa IN (9993, 20119, 15319, 15320, 10000650))
  );

-- Stone, SafraPay e Cielo Lio: uma "Historico" inativa por filial
INSERT INTO tblmaquineta (apelido, codfilial, codpessoa, inativo)
SELECT DISTINCT
    CASE h.grupo
        WHEN 'S' THEN 'Histórico Stone'
        WHEN 'F' THEN 'Histórico SafraPay'
        WHEN 'C' THEN 'Histórico Cielo Lio'
    END,
    h.codfilial,
    CASE h.grupo
        WHEN 'S' THEN 9993
        WHEN 'F' THEN 20119
        WHEN 'C' THEN (SELECT codpessoa FROM tblpessoa WHERE cnpj = 1027058000191 ORDER BY codpessoa LIMIT 1)
    END,
    now()
FROM _historico h
WHERE h.grupo IN ('S', 'F', 'C')
  AND NOT EXISTS (
      SELECT 1 FROM tblmaquineta m
      WHERE m.codfilial = h.codfilial
        AND m.integracao IS NULL
        AND m.apelido = CASE h.grupo
            WHEN 'S' THEN 'Histórico Stone'
            WHEN 'F' THEN 'Histórico SafraPay'
            WHEN 'C' THEN 'Histórico Cielo Lio'
        END
  );

UPDATE tblnegocioformapagamento nfp
SET codmaquineta = m.codmaquineta
FROM _historico h
JOIN tblmaquineta m ON (
    m.codfilial = h.codfilial
    AND m.integracao IS NULL
    AND m.apelido = CASE h.grupo
        WHEN 'S' THEN 'Histórico Stone'
        WHEN 'F' THEN 'Histórico SafraPay'
        WHEN 'C' THEN 'Histórico Cielo Lio'
    END
)
WHERE h.grupo IN ('S', 'F', 'C')
  AND nfp.codnegocioformapagamento = h.codnegocioformapagamento;

-- Brasil Card, Le Card e MultVale: sempre foram pelo site
UPDATE tblnegocioformapagamento nfp
SET codmaquineta = (
    SELECT m.codmaquineta FROM tblmaquineta m
    WHERE m.codpessoa = h.codpessoa AND m.compartilhada AND m.integracao IS NULL
    ORDER BY (m.inativo IS NULL) DESC, m.codmaquineta
    LIMIT 1
)
FROM _historico h
WHERE h.grupo = 'W'
  AND nfp.codnegocioformapagamento = h.codnegocioformapagamento;

-- Conferencia: toda venda com pedido PagarMe/Saurus ou serial tem maquineta
DO $$
DECLARE
    v_erro bigint;
BEGIN
    SELECT count(*) INTO v_erro
    FROM tblnegocioformapagamento
    WHERE codmaquineta IS NULL
      AND (codpagarmepedido IS NOT NULL
           OR codsauruspedido IS NOT NULL
           OR (serialmaquineta IS NOT NULL AND trim(serialmaquineta) <> '' AND codpessoa IS NOT NULL)
           OR codformapagamento IN (5603, 5605)
           OR (codformapagamento = 2010 AND codpessoa IN (9993, 20119, 15319, 15320, 10000650)));
    IF v_erro > 0 THEN
        RAISE EXCEPTION '% pagamentos de cartao ficaram sem maquineta. Carga abortada.', v_erro;
    END IF;
END $$;

SELECT
    CASE
        WHEN m.integracao = 'P' THEN 'PagarMe'
        WHEN m.integracao = 'S' THEN 'Saurus'
        WHEN m.compartilhada THEN 'Site'
        WHEN m.apelido LIKE 'Histórico %' THEN 'Historico'
        ELSE 'Manual'
    END AS maquinetas,
    count(*) FILTER (WHERE m.inativo IS NULL) AS ativas,
    count(*) FILTER (WHERE m.inativo IS NOT NULL) AS inativas
FROM tblmaquineta m
GROUP BY 1
ORDER BY 1;

SELECT
    CASE
        WHEN m.integracao = 'P' THEN 'PagarMe'
        WHEN m.integracao = 'S' THEN 'Saurus'
        WHEN m.compartilhada THEN 'Site'
        WHEN m.apelido LIKE 'Histórico %' THEN 'Historico'
        WHEN m.codmaquineta IS NOT NULL THEN 'Manual'
        ELSE 'Sem maquineta'
    END AS pagamentos,
    count(*)
FROM tblnegocioformapagamento nfp
LEFT JOIN tblmaquineta m ON (m.codmaquineta = nfp.codmaquineta)
WHERE nfp.codmaquineta IS NOT NULL
   OR nfp.codformapagamento IN (2010, 5603, 5605, 5608)
GROUP BY 1
ORDER BY 1;

COMMIT;

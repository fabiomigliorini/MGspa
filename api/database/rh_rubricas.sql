-- =====================================================================
-- Modulo RH (Mg\Rh) — Refatoracao "Metas & Variaveis" / Cartao Bee (Fase 1)
--
-- O QUE MUDA (preservando dados):
--   1. Catalogo de rubricas reutilizavel: nova tabela tblrubrica.
--      tblcolaboradorrubrica passa a referenciar o catalogo (codrubrica) e
--      ganha observacao, valorunitario, quantidade.
--   2. Novo tipo de calculo 'Q' (Unitario x Quantidade): valor = valorunitario
--      x quantidade (ex.: marmita R$15 x 26 dias). tipovalor agora aceita P/F/Q.
--   3. Rateio COLAPSADO no percentual: some percentualrateio e a logica de pool.
--      Para rubricas percentuais sobre indicador de SETOR, dobra-se o rateio no
--      proprio percentual (50% de 5% = 2,5%). Assim o calculo bate sem a tabela.
--   4. diastrabalhados e descontaabsenteismo ELIMINADOS (proracao automatica nao
--      funcionava; a necessidade real "pagar por dia" vira o tipo 'Q').
--   5. codsetor do colaborador vai direto pro tblperiodocolaborador (setor "casa"
--      do periodo) -> tblperiodocolaboradorsetor e' DROPADA, preservando os
--      totais por setor/unidade no Resumo.
--
-- Vale/adiantamento (titulo a debito) e' Fase 2 — nao mexe aqui.
--
-- Schema mgsis. Idempotencia parcial: cria backups _bkp_* recuperaveis e roda
-- em transacao (ON_ERROR_STOP + BEGIN/COMMIT) — se qualquer passo falhar, NADA
-- e' aplicado. Rodar UMA vez (dropa colunas/tabela). Para reverter: restaurar
-- dos _bkp_*.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

-- ---------------------------------------------------------------------
-- 0) Backups recuperaveis (persistem apos o commit)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS _bkp_colaboradorrubrica;
CREATE TABLE _bkp_colaboradorrubrica AS SELECT * FROM tblcolaboradorrubrica;
DROP TABLE IF EXISTS _bkp_periodocolaboradorsetor;
CREATE TABLE _bkp_periodocolaboradorsetor AS SELECT * FROM tblperiodocolaboradorsetor;
DROP TABLE IF EXISTS _bkp_periodocolaborador;
CREATE TABLE _bkp_periodocolaborador AS SELECT * FROM tblperiodocolaborador;

-- Normalizador para casar grafias (Gratificacao=Gratificação, 1o lugar=1o Lugar)
CREATE OR REPLACE FUNCTION rh_norm(t text) RETURNS text AS $$
  SELECT lower(btrim(translate(coalesce(t, ''),
    'ÁÀÂÃÄÉÈÊËÍÌÎÏÓÒÔÕÖÚÙÛÜÇáàâãäéèêëíìîïóòôõöúùûüç',
    'AAAAAEEEEIIIIOOOOOUUUUCaaaaaeeeeiiiiooooouuuuc')));
$$ LANGUAGE sql IMMUTABLE;

-- ---------------------------------------------------------------------
-- 1) Colapsa o rateio no percentual (antes de dropar a tabela de vinculo)
--    percentual_novo = percentual * (percentualrateio/100)
--    So' rubricas PERCENTUAIS sobre indicador de SETOR usavam rateio.
--    Sem vinculo -> rateio 0 (no calc antigo o valor era 0).
--    Amplia a precisao do percentual pra nao perder o produto (3+3 decimais).
-- ---------------------------------------------------------------------
ALTER TABLE tblcolaboradorrubrica ALTER COLUMN percentual TYPE numeric(9,6);

UPDATE tblcolaboradorrubrica cr
SET percentual = round(cr.percentual * (coalesce((
      SELECT pcs.percentualrateio
      FROM tblperiodocolaboradorsetor pcs
      WHERE pcs.codperiodocolaboradorsetor = coalesce(
              cr.codperiodocolaboradorsetor,
              (SELECT p2.codperiodocolaboradorsetor
                 FROM tblperiodocolaboradorsetor p2
                WHERE p2.codperiodocolaborador = cr.codperiodocolaborador
                ORDER BY p2.codperiodocolaboradorsetor
                LIMIT 1))
    ), 0) / 100.0), 6)
FROM tblindicador i
WHERE cr.codindicador = i.codindicador
  AND i.tipo = 'S'
  AND cr.tipovalor = 'P'
  AND cr.percentual IS NOT NULL;

-- ---------------------------------------------------------------------
-- 2) codsetor "casa" no tblperiodocolaborador (do primeiro vinculo)
-- ---------------------------------------------------------------------
ALTER TABLE tblperiodocolaborador ADD COLUMN IF NOT EXISTS codsetor bigint;

UPDATE tblperiodocolaborador pc
SET codsetor = (
  SELECT pcs.codsetor
  FROM tblperiodocolaboradorsetor pcs
  WHERE pcs.codperiodocolaborador = pc.codperiodocolaborador
  ORDER BY pcs.codperiodocolaboradorsetor
  LIMIT 1);

ALTER TABLE tblperiodocolaborador
  ADD CONSTRAINT tblperiodocolaborador_codsetor_fkey
  FOREIGN KEY (codsetor) REFERENCES tblsetor(codsetor);

CREATE INDEX IF NOT EXISTS idx_periodocolab_setor
  ON tblperiodocolaborador(codsetor);

-- ---------------------------------------------------------------------
-- 3) Catalogo de rubricas (tblrubrica)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tblrubrica (
  codrubrica          bigserial PRIMARY KEY,
  descricao           varchar(200) NOT NULL,
  tipovalor           char(1) NOT NULL DEFAULT 'F',
  tipocondicao        char(1),
  valorpadrao         numeric(14,2),
  valorunitariopadrao numeric(14,2),
  recorrente          boolean NOT NULL DEFAULT true,
  inativo             timestamp(0) without time zone,
  criacao             timestamp(0) without time zone NOT NULL DEFAULT now(),
  codusuariocriacao   bigint,
  alteracao           timestamp(0) without time zone NOT NULL DEFAULT now(),
  codusuarioalteracao bigint,
  CONSTRAINT chk_rubrica_tipovalor CHECK (tipovalor = ANY (ARRAY['P','F','Q']::bpchar[])),
  CONSTRAINT chk_rubrica_tipocondicao CHECK (tipocondicao IS NULL OR tipocondicao = ANY (ARRAY['M','R']::bpchar[]))
);

-- Semeia o catalogo a partir das descricoes existentes, agrupando por grafia
-- normalizada e escolhendo a variante mais frequente como canonica.
-- valorpadrao = valor fixo mais comum daquela rubrica (util p/ marmita etc).
INSERT INTO tblrubrica (descricao, tipovalor, tipocondicao, recorrente, valorpadrao)
SELECT descricao, tipovalor, tipocondicao, recorrente,
       CASE WHEN tipovalor = 'F' THEN valorfixo ELSE NULL END
FROM (
  SELECT descricao, tipovalor, tipocondicao, recorrente, valorfixo,
         row_number() OVER (PARTITION BY rh_norm(descricao) ORDER BY cnt DESC, descricao) rn
  FROM (
    SELECT descricao, tipovalor, tipocondicao,
           bool_or(recorrente) recorrente,
           mode() WITHIN GROUP (ORDER BY valorfixo) valorfixo,
           count(*) cnt
    FROM tblcolaboradorrubrica
    GROUP BY descricao, tipovalor, tipocondicao
  ) g
) h
WHERE rn = 1;

-- Garante a rubrica de Presenteismo (R$200, benef. novo)
INSERT INTO tblrubrica (descricao, tipovalor, valorpadrao, recorrente)
SELECT 'Presenteísmo', 'F', 200, true
WHERE NOT EXISTS (SELECT 1 FROM tblrubrica WHERE rh_norm(descricao) = rh_norm('Presenteísmo'));

-- ---------------------------------------------------------------------
-- 4) tblcolaboradorrubrica: novas colunas + vinculo ao catalogo,
--    aceita tipo 'Q', dropa setor/absenteismo
-- ---------------------------------------------------------------------
ALTER TABLE tblcolaboradorrubrica DROP CONSTRAINT IF EXISTS chk_colabrub_tipovalor;
ALTER TABLE tblcolaboradorrubrica
  ADD CONSTRAINT chk_colabrub_tipovalor CHECK (tipovalor = ANY (ARRAY['P','F','Q']::bpchar[]));

ALTER TABLE tblcolaboradorrubrica ADD COLUMN IF NOT EXISTS codrubrica bigint;
ALTER TABLE tblcolaboradorrubrica ADD COLUMN IF NOT EXISTS observacao text;
ALTER TABLE tblcolaboradorrubrica ADD COLUMN IF NOT EXISTS valorunitario numeric(14,2);
ALTER TABLE tblcolaboradorrubrica ADD COLUMN IF NOT EXISTS quantidade numeric(10,2);

UPDATE tblcolaboradorrubrica cr
SET codrubrica = r.codrubrica
FROM tblrubrica r
WHERE rh_norm(cr.descricao) = rh_norm(r.descricao);

ALTER TABLE tblcolaboradorrubrica
  ADD CONSTRAINT tblcolaboradorrubrica_codrubrica_fkey
  FOREIGN KEY (codrubrica) REFERENCES tblrubrica(codrubrica);
CREATE INDEX IF NOT EXISTS idx_colabrubrica_rubrica ON tblcolaboradorrubrica(codrubrica);

ALTER TABLE tblcolaboradorrubrica DROP CONSTRAINT IF EXISTS tblcolaboradorrubrica_codperiodocolaboradorsetor_fkey;
ALTER TABLE tblcolaboradorrubrica DROP COLUMN IF EXISTS codperiodocolaboradorsetor;
ALTER TABLE tblcolaboradorrubrica DROP COLUMN IF EXISTS descontaabsenteismo;

-- ---------------------------------------------------------------------
-- 5) Aposenta a tabela de vinculo colaborador-setor (rateio/dias)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS tblperiodocolaboradorsetor;

DROP FUNCTION IF EXISTS rh_norm(text);

COMMIT;

-- ---------------------------------------------------------------------
-- Verificacao (fora da transacao)
-- ---------------------------------------------------------------------
\echo '== catalogo tblrubrica (itens) =='
SELECT count(*) AS rubricas_catalogo FROM tblrubrica;
\echo '== colaboradorrubrica: mapeadas ao catalogo x avulsas =='
SELECT count(*) FILTER (WHERE codrubrica IS NOT NULL) AS com_catalogo,
       count(*) FILTER (WHERE codrubrica IS NULL)     AS avulsas,
       count(*) AS total
FROM tblcolaboradorrubrica;
\echo '== periodocolaborador com codsetor preenchido =='
SELECT count(*) FILTER (WHERE codsetor IS NOT NULL) AS com_setor,
       count(*) FILTER (WHERE codsetor IS NULL)     AS sem_setor
FROM tblperiodocolaborador;
\echo '== amostra do recalculo de % (setor-percentual): antes x depois =='
SELECT n.codcolaboradorrubrica, n.descricao,
       b.percentual AS pct_antigo, n.percentual AS pct_novo
FROM tblcolaboradorrubrica n
JOIN _bkp_colaboradorrubrica b USING (codcolaboradorrubrica)
JOIN tblindicador i ON i.codindicador = n.codindicador AND i.tipo = 'S'
WHERE n.tipovalor = 'P' AND b.percentual IS DISTINCT FROM n.percentual
ORDER BY n.codcolaboradorrubrica
LIMIT 20;

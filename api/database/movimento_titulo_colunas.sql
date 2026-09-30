-- =====================================================================
-- Movimento de titulo numa linha so' (milestone M1 do plano
-- backlog/docs/doc-3, TASK-188).
--
-- O QUE MUDA em tblmovimentotitulo
--   valor     -> principal  efeito no saldo do titulo, COM SINAL (o que
--                           era valor; positivo aumenta o que nos devem)
--   juros, multa, desconto  POSITIVOS, sempre (CHECK >= 0)
--   total                   o valor efetivo da baixa, mesmo sinal do
--                           principal: o dinheiro que andou, ou o que foi
--                           levado ao agrupamento. Implantacao, ajuste e
--                           demais movimentos sem baixa: 0.
-- Na baixa: |total| = |principal| + juros + multa - desconto.
--
-- CONVERSAO DO HISTORICO: antes, juros (400), multa (401) e desconto (500)
-- eram linhas separadas da baixa, e o estorno delas tambem (940, 941, 950
-- ou, depois do M0, o mesmo tipo apontando para o original). Cada grupo
-- = o mesmo titulo na mesma
--   liquidacao (codliquidacaotitulo),
--   retorno de boleto Bradesco (codboletoretorno),
--   agrupamento (codtituloagrupamento) ou
--   boleto BB pela API (codtituloboleto),
-- separado em baixa e estorno, com exatamente UMA linha de baixa
-- (600, 601, 901; no estorno 930, 991 ou a do tipo original com ponteiro).
-- As linhas de juros/multa/desconto do grupo sao somadas nas colunas da
-- linha de baixa e apagadas:
--   total     = valor antigo da linha de baixa
--   principal = valor antigo da baixa + soma dos valores das incorporadas
--               (o saldo do titulo nao muda)
--   juros/multa/desconto = soma das incorporadas, no sentido da baixa
-- O sentido e' o sinal da propria baixa; baixa de valor zero (titulo
-- quitado so' com desconto) usa a natureza do titulo.
--
-- FICAM COMO LINHA PROPRIA (tipo antigo, principal = valor antigo,
-- colunas zeradas): as que nao tem baixa unica no grupo e as que dariam
-- juros, multa ou desconto negativo (titulos antigos com sinal trocado).
-- Levantamento de 29/09/2026 em dev: 4 linhas de 2013 numa liquidacao
-- estornada 3 vezes e ~10 grupos de sinal trocado.
--
-- Os tipos 400, 401, 500, 940, 941 e 950 deixam de ser usados e ficam
-- inativos (so' para o historico que sobrou).
--
-- CONFERENCIA dentro do proprio script (secao 2 aborta com ROLLBACK se
-- nao fechar): saldo de cada titulo igual; soma de juros, multa e desconto
-- por mes igual (colunas + linhas que ficaram); total de cada liquidacao
-- igual.
--
-- QUEM MAIS LE/GRAVA: o MGsis (NFe de Terceiros) grava implantacao e
-- recalcula o saldo pela coluna do movimento. Os models Titulo e
-- MovimentoTitulo de la' foram ajustados para principal: SOBEM JUNTO.
--
-- DEPLOY: script + codigo novo do MGspa + MGsis na MESMA janela, com a API
-- parada (o codigo novo grava em principal/total; o velho, em valor).
--
-- Idempotente: o rename so' acontece se valor ainda existir; a conversao
-- so' encontra grupo para juntar enquanto houver linha antiga; o total
-- das baixas so' e' preenchido onde ainda esta' zerado.
-- =====================================================================

\set ON_ERROR_STOP on

-- ---------------------------------------------------------------------
-- 1) Estrutura
-- ---------------------------------------------------------------------
BEGIN;

-- tabela quente: se alguem segura lock, desiste em 5s em vez de travar o
-- financeiro e o PDV. Nunca remover estes dois SET.
SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '60s';

DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns
             WHERE table_schema = current_schema()
               AND table_name = 'tblmovimentotitulo'
               AND column_name = 'valor') THEN
    ALTER TABLE tblmovimentotitulo RENAME COLUMN valor TO principal;
  END IF;
END $$;

-- DEFAULT constante: no PG 11+ o ADD COLUMN nao reescreve a tabela
ALTER TABLE tblmovimentotitulo ADD COLUMN IF NOT EXISTS juros    numeric(14,2) NOT NULL DEFAULT 0;
ALTER TABLE tblmovimentotitulo ADD COLUMN IF NOT EXISTS multa    numeric(14,2) NOT NULL DEFAULT 0;
ALTER TABLE tblmovimentotitulo ADD COLUMN IF NOT EXISTS desconto numeric(14,2) NOT NULL DEFAULT 0;
ALTER TABLE tblmovimentotitulo ADD COLUMN IF NOT EXISTS total    numeric(14,2) NOT NULL DEFAULT 0;

COMMIT;

-- ---------------------------------------------------------------------
-- 2) Conversao do historico + conferencia
-- ---------------------------------------------------------------------
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '30min';

-- ninguem grava movimento enquanto a conversao roda
LOCK TABLE tblmovimentotitulo IN SHARE ROW EXCLUSIVE MODE;

-- fotos de antes -------------------------------------------------------
CREATE TEMP TABLE _antes_saldo ON COMMIT DROP AS
  SELECT codtitulo, sum(principal) AS saldo
    FROM tblmovimentotitulo
   GROUP BY codtitulo;

-- juros/multa/desconto por mes, com sinal (como estava no banco)
CREATE TEMP TABLE _antes_mes ON COMMIT DROP AS
  SELECT date_trunc('month', transacao) AS mes,
         coalesce(sum(principal) FILTER (WHERE codtipomovimentotitulo IN (400, 940)), 0) AS juros,
         coalesce(sum(principal) FILTER (WHERE codtipomovimentotitulo IN (401, 941)), 0) AS multa,
         coalesce(sum(principal) FILTER (WHERE codtipomovimentotitulo IN (500, 950)), 0) AS desconto
    FROM tblmovimentotitulo
   WHERE codtipomovimentotitulo IN (400, 401, 500, 940, 941, 950)
   GROUP BY 1;

-- total da liquidacao = soma das baixas 600 nao estornadas (o que o
-- MovimentoTituloService::recalcularLiquidacao grava)
CREATE TEMP TABLE _antes_liq ON COMMIT DROP AS
  SELECT codliquidacaotitulo,
         sum(CASE WHEN total <> 0 OR juros <> 0 OR multa <> 0 OR desconto <> 0
                  THEN total ELSE principal END) AS valor
    FROM tblmovimentotitulo
   WHERE codliquidacaotitulo IS NOT NULL
     AND codtipomovimentotitulo = 600
     AND codmovimentotituloestorno IS NULL
   GROUP BY 1;

-- linhas candidatas ----------------------------------------------------
-- papel: J juros, M multa, D desconto, X baixa
-- est:   lado do estorno (tipos 9xx de estorno ou ponteiro para o original)
CREATE TEMP TABLE _mov ON COMMIT DROP AS
  SELECT m.codmovimentotitulo, m.codtitulo, m.principal, m.codmovimentotituloestorno,
         CASE WHEN m.codliquidacaotitulo  IS NOT NULL THEN 'L'
              WHEN m.codboletoretorno     IS NOT NULL THEN 'B'
              WHEN m.codtituloagrupamento IS NOT NULL THEN 'A'
              WHEN m.codtituloboleto      IS NOT NULL THEN 'T' END AS k,
         coalesce(m.codliquidacaotitulo, m.codboletoretorno,
                  m.codtituloagrupamento, m.codtituloboleto) AS kid,
         CASE WHEN m.codtipomovimentotitulo IN (400, 940) THEN 'J'
              WHEN m.codtipomovimentotitulo IN (401, 941) THEN 'M'
              WHEN m.codtipomovimentotitulo IN (500, 950) THEN 'D'
              ELSE 'X' END AS papel,
         (m.codtipomovimentotitulo IN (930, 940, 941, 950, 991)
          OR m.codmovimentotituloestorno IS NOT NULL) AS est
    FROM tblmovimentotitulo m
   WHERE m.codtipomovimentotitulo IN (400, 401, 500, 600, 601, 901, 930, 940, 941, 950, 991)
     -- linha ja' convertida nao entra de novo
     AND m.juros = 0 AND m.multa = 0 AND m.desconto = 0 AND m.total = 0;

-- estorno feito depois do M0 nem sempre copiou o vinculo: herda o grupo
-- do movimento que ele desfez
UPDATE _mov e
   SET k = o.k, kid = o.kid
  FROM _mov o
 WHERE e.codmovimentotituloestorno = o.codmovimentotitulo
   AND e.k IS NULL;

-- grupos que se juntam -------------------------------------------------
CREATE TEMP TABLE _grp ON COMMIT DROP AS
  SELECT g.*,
         -- sentido da baixa: o sinal dela; baixa zero usa a natureza do
         -- titulo (baixa de receber diminui, estorno aumenta)
         coalesce(nullif(sign(g.vx), 0),
                  CASE WHEN tt.natureza = 'R' THEN -1 ELSE 1 END
                  * CASE WHEN g.est THEN -1 ELSE 1 END) AS s
    FROM (
          SELECT codtitulo, k, kid, est,
                 count(*) FILTER (WHERE papel = 'X') AS nbx,
                 count(*) FILTER (WHERE papel <> 'X') AS nacr,
                 max(codmovimentotitulo) FILTER (WHERE papel = 'X') AS codbaixa,
                 coalesce(sum(principal) FILTER (WHERE papel = 'X'), 0) AS vx,
                 coalesce(sum(principal) FILTER (WHERE papel = 'J'), 0) AS vj,
                 coalesce(sum(principal) FILTER (WHERE papel = 'M'), 0) AS vm,
                 coalesce(sum(principal) FILTER (WHERE papel = 'D'), 0) AS vd
            FROM _mov
           WHERE k IS NOT NULL
           GROUP BY 1, 2, 3, 4
         ) g
    JOIN tbltitulo t USING (codtitulo)
    JOIN tbltipotitulo tt USING (codtipotitulo)
   WHERE g.nbx = 1 AND g.nacr > 0;

-- juros e multa aumentam o titulo (sentido contrario ao da baixa);
-- desconto diminui (mesmo sentido da baixa). Negativo = sinal trocado no
-- historico: o grupo fica como esta'.
DELETE FROM _grp WHERE -s * vj < 0 OR -s * vm < 0 OR s * vd < 0;

-- incorporadas e para onde vao
CREATE TEMP TABLE _inc ON COMMIT DROP AS
  SELECT m.codmovimentotitulo, g.codbaixa
    FROM _mov m
    JOIN _grp g USING (codtitulo, k, kid, est)
   WHERE m.papel <> 'X';

-- conversao ------------------------------------------------------------
UPDATE tblmovimentotitulo b
   SET total     = g.vx,
       principal = g.vx + g.vj + g.vm + g.vd,
       juros     = -g.s * g.vj,
       multa     = -g.s * g.vm,
       desconto  =  g.s * g.vd
  FROM _grp g
 WHERE b.codmovimentotitulo = g.codbaixa;

-- quem apontava para uma incorporada passa a apontar para a baixa dela
UPDATE tblmovimentotitulo m
   SET codmovimentotituloestorno = i.codbaixa
  FROM _inc i
 WHERE m.codmovimentotituloestorno = i.codmovimentotitulo
   AND NOT EXISTS (SELECT 1 FROM _inc x WHERE x.codmovimentotitulo = m.codmovimentotitulo);

DELETE FROM tblmovimentotitulo m
 USING _inc i
 WHERE m.codmovimentotitulo = i.codmovimentotitulo;

-- baixa sem juros/multa/desconto: total = principal
--   300/933 amortizacao (vale usado no PDV), 600/930 liquidacao,
--   601 RH, 610/910 cobranca, 901/991 agrupamento
UPDATE tblmovimentotitulo
   SET total = principal
 WHERE codtipomovimentotitulo IN (300, 600, 601, 610, 901, 910, 930, 933, 991)
   AND total = 0 AND juros = 0 AND multa = 0 AND desconto = 0
   AND principal <> 0;

-- conferencia ----------------------------------------------------------
DO $$
DECLARE
  v_grupos  bigint;
  v_linhas  bigint;
  v_sobra   bigint;
  v_erro    bigint;
BEGIN
  SELECT count(*) INTO v_grupos FROM _grp;
  SELECT count(*) INTO v_linhas FROM _inc;
  SELECT count(*) INTO v_sobra
    FROM tblmovimentotitulo WHERE codtipomovimentotitulo IN (400, 401, 500, 940, 941, 950);
  RAISE NOTICE 'Grupos juntados: %, linhas incorporadas: %, linhas de juros/multa/desconto que ficaram: %',
    v_grupos, v_linhas, v_sobra;

  -- saldo de cada titulo
  SELECT count(*) INTO v_erro
    FROM _antes_saldo a
    FULL JOIN (SELECT codtitulo, sum(principal) AS saldo
                 FROM tblmovimentotitulo GROUP BY codtitulo) d USING (codtitulo)
   WHERE a.saldo IS DISTINCT FROM d.saldo;
  IF v_erro > 0 THEN
    RAISE EXCEPTION 'Saldo mudou em % titulos. Conversao abortada.', v_erro;
  END IF;

  -- juros/multa/desconto por mes (com sinal): colunas + linhas que ficaram
  SELECT count(*) INTO v_erro
    FROM _antes_mes a
    FULL JOIN (
          SELECT mes, sum(juros) AS juros, sum(multa) AS multa, sum(desconto) AS desconto
            FROM (
                  SELECT date_trunc('month', m.transacao) AS mes,
                         -g.s * m.juros AS juros, -g.s * m.multa AS multa, g.s * m.desconto AS desconto
                    FROM tblmovimentotitulo m
                    JOIN _grp g ON g.codbaixa = m.codmovimentotitulo
                  UNION ALL
                  SELECT date_trunc('month', transacao),
                         CASE WHEN codtipomovimentotitulo IN (400, 940) THEN principal ELSE 0 END,
                         CASE WHEN codtipomovimentotitulo IN (401, 941) THEN principal ELSE 0 END,
                         CASE WHEN codtipomovimentotitulo IN (500, 950) THEN principal ELSE 0 END
                    FROM tblmovimentotitulo
                   WHERE codtipomovimentotitulo IN (400, 401, 500, 940, 941, 950)
                 ) x
           GROUP BY 1
         ) d USING (mes)
   WHERE coalesce(a.juros, 0)    <> coalesce(d.juros, 0)
      OR coalesce(a.multa, 0)    <> coalesce(d.multa, 0)
      OR coalesce(a.desconto, 0) <> coalesce(d.desconto, 0);
  IF v_erro > 0 THEN
    RAISE EXCEPTION 'Juros/multa/desconto por mes nao fecha em % meses. Conversao abortada.', v_erro;
  END IF;

  -- total de cada liquidacao
  SELECT count(*) INTO v_erro
    FROM _antes_liq a
    FULL JOIN (SELECT codliquidacaotitulo, sum(total) AS valor
                 FROM tblmovimentotitulo
                WHERE codliquidacaotitulo IS NOT NULL
                  AND codtipomovimentotitulo = 600
                  AND codmovimentotituloestorno IS NULL
                GROUP BY 1) d USING (codliquidacaotitulo)
   WHERE a.valor IS DISTINCT FROM d.valor;
  IF v_erro > 0 THEN
    RAISE EXCEPTION 'Total mudou em % liquidacoes. Conversao abortada.', v_erro;
  END IF;

  -- regra da baixa convertida: |total| = |principal| + juros + multa - desconto
  SELECT count(*) INTO v_erro
    FROM tblmovimentotitulo m
    JOIN _grp g ON g.codbaixa = m.codmovimentotitulo
   WHERE m.principal - m.total <> -g.s * (m.juros + m.multa - m.desconto);
  IF v_erro > 0 THEN
    RAISE EXCEPTION '% baixas convertidas com principal/total inconsistente. Conversao abortada.', v_erro;
  END IF;
END $$;

COMMIT;

-- ---------------------------------------------------------------------
-- 3) Travas e catalogo
-- ---------------------------------------------------------------------
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '10min';

ALTER TABLE tblmovimentotitulo ALTER COLUMN principal SET NOT NULL;

DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_constraint
                 WHERE conname = 'tblmovimentotitulo_acrescimos_positivos_chk'
                   AND conrelid = to_regclass('tblmovimentotitulo')) THEN
    ALTER TABLE tblmovimentotitulo
      ADD CONSTRAINT tblmovimentotitulo_acrescimos_positivos_chk
      CHECK (juros >= 0 AND multa >= 0 AND desconto >= 0);
  END IF;
END $$;

-- juros, multa e desconto agora sao colunas da baixa
UPDATE tbltipomovimentotitulo
   SET inativo = now()
 WHERE codtipomovimentotitulo IN (400, 401, 500, 940, 941, 950)
   AND inativo IS NULL;

COMMIT;

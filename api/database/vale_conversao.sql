-- =====================================================================
-- Vale compras: CONVERSAO DO LEGADO e LIMPEZA (milestone 9 do plano).
--
-- Cada vale do sistema antigo (tblvalecompra, vendido fora do negocio)
-- vira o que um vale vendido hoje no PDV grava:
--
--   1 tblnegocio                 fechado (ou cancelado), SEM item de
--                                mercadoria, codpdv NULL, natureza Venda
--   N tblnegocioformapagamento   uma por pagamento do vale antigo
--   1 tblnegociovale             apontando para o MESMO titulo de credito
--   N tblnegociovaleprodutobarra os itens do kit
--
-- e os 313 titulos a prazo (tipo 240) que pendiam do pagamento antigo
-- passam a pender do pagamento novo. Depois disso as tres tabelas antigas,
-- a coluna tbltitulo.codvalecompraformapagamento e a flag
-- tblformapagamento.valecompra sao DROPADAS, na mesma transacao.
--
-- NENHUM TITULO E' TOCADO alem da FK dos 313: valor, saldo, numero
-- (V00001234-CR, impresso no papel), vencimento, estorno e movimentos
-- ficam como estao. O codigo de barras do papel e' VAL+codtitulo, entao o
-- papel antigo continua resgatavel sem mudar nada.
--
-- MARCADOR DO CONVERTIDO: tblnegociovale.codvalecompra IS NOT NULL (o
-- numero antigo, que esta no papel). codpdv fica NULL: as triggers de
-- total do negocio foram removidas (25/09/2026), entao nada recalcula os
-- totais gravados aqui -- eles saem prontos deste script.
--
-- CREDITO LIQUIDO x FACE. No sistema antigo o credito era o valor PAGO
-- (total = totalprodutos - desconto); no novo, o credito e' a face
-- (valorvale = valorprodutos + valoravulso). Para os vales antigos com
-- desconto, o desconto entra como AVULSO NEGATIVO:
--
--   vale 3713: valorprodutos 205,14 + valoravulso -10,26 = valorvale 194,88
--                                                        = credito do titulo
--
-- Assim a regra "valorvale = produtos + avulso = credito" vale para todos
-- os vales, antigos e novos, e nada de resgate/DIMP/escopo precisa saber
-- que o vale e' convertido. valordesconto do vale e do negocio fica zerado:
-- o desconto ja' esta' dentro da face.
--
-- CANCELADO = vale antigo inativo OU titulo de credito estornado. Os dois
-- sempre andaram juntos, menos no vale 3130 (titulo estornado em
-- 15/01/2025, vale nao inativado): o titulo manda, ele vira cancelado.
-- tblnegociovale.inativo fica NULL em todos, como num cancelamento feito
-- pelo PDV hoje (inativo no vale e' "excluido antes de fechar").
--
-- ANOMALIA PRESERVADA: o vale 2348 (131,82) tem dois pagamentos de 131,82
-- (dinheiro sem titulo e crediario com titulo quitado). Vai como esta,
-- fiel ao legado, com a observacao no negocio.
--
-- Idempotente: so' age enquanto tblvalecompra existir; depois do DROP,
-- rodar de novo e' no-op. Tudo numa transacao, e o DROP so' acontece se
-- TODAS as conferencias do fim fecharem -- senao RAISE e rollback, e o
-- legado fica intacto.
--
-- Pre-requisitos: vale_catalogo.sql e vale.sql.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

-- DROP COLUMN em tbltitulo e tblformapagamento pega ACCESS EXCLUSIVE.
-- Se alguem estiver com transacao ociosa segurando lock nelas, o script
-- desiste em 5s em vez de travar o sistema na fila.
SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '300s';

DO $$
DECLARE
  v_qtd     bigint;
  v_qtd2    bigint;
  v_soma    numeric;
  v_soma2   numeric;
  v_soma3   numeric;
  v_lista   text;
  v_tit_qtd bigint;
  v_tit_deb numeric;
  v_tit_sal numeric;
BEGIN

IF to_regclass('tblvalecompra') IS NULL THEN
  RAISE NOTICE 'vale_conversao.sql: ja aplicado, nada a fazer.';
  RETURN;
END IF;

IF to_regclass('tblnegociovale') IS NULL THEN
  RAISE EXCEPTION 'vale_conversao.sql: rode vale.sql antes deste.';
END IF;

-- ---------------------------------------------------------------------
-- 0) Pre-condicoes: falha alta, listando os ids, em vez de pular linha
-- ---------------------------------------------------------------------

IF EXISTS (SELECT 1 FROM tblnegociovale WHERE codvalecompra IS NOT NULL) THEN
  RAISE EXCEPTION 'vale_conversao.sql: ja existem vales convertidos com o legado ainda de pe. Nada foi feito.';
END IF;

SELECT string_agg(DISTINCT v.codfilial::text, ', ') INTO v_lista
  FROM tblvalecompra v
 WHERE NOT EXISTS (SELECT 1 FROM tblestoquelocal el WHERE el.codfilial = v.codfilial);
IF v_lista IS NOT NULL THEN
  RAISE EXCEPTION 'vale_conversao.sql: filial sem local de estoque: %', v_lista;
END IF;

SELECT string_agg(DISTINCT f.codformapagamento::text, ', ') INTO v_lista
  FROM tblvalecompraformapagamento f
 WHERE f.codformapagamento NOT IN (1010, 1020, 1030, 1099, 2010, 3010, 3020, 4100, 5100, 5604, 5605, 5606);
IF v_lista IS NOT NULL THEN
  RAISE EXCEPTION 'vale_conversao.sql: forma de pagamento sem tPag mapeado: %', v_lista;
END IF;

SELECT string_agg(v.codvalecompra::text, ', ') INTO v_lista
  FROM tblvalecompra v
  LEFT JOIN tbltitulo t ON (t.codtitulo = v.codtitulo)
 WHERE t.codtitulo IS NULL;
IF v_lista IS NOT NULL THEN
  RAISE EXCEPTION 'vale_conversao.sql: vale sem titulo de credito: %', v_lista;
END IF;

SELECT string_agg(v.codvalecompra::text, ', ') INTO v_lista
  FROM tblvalecompra v
  JOIN tbltitulo t ON (t.codtitulo = v.codtitulo)
 WHERE round(coalesce(t.credito, 0) - coalesce(t.debito, 0), 2) <> v.total
    OR v.total <> v.totalprodutos - coalesce(v.desconto, 0);
IF v_lista IS NOT NULL THEN
  RAISE EXCEPTION 'vale_conversao.sql: credito do titulo nao bate com o vale: %', v_lista;
END IF;

SELECT string_agg(t.codtitulo::text, ', ') INTO v_lista
  FROM tbltitulo t
 WHERE t.codvalecompraformapagamento IS NOT NULL
   AND t.codnegocioformapagamento IS NOT NULL;
IF v_lista IS NOT NULL THEN
  RAISE EXCEPTION 'vale_conversao.sql: titulo pendurado em pagamento de vale E de negocio: %', v_lista;
END IF;

-- ---------------------------------------------------------------------
-- 1) Mapas de chave: codigo antigo -> codigo novo
-- ---------------------------------------------------------------------

CREATE TEMP TABLE _m9_vale ON COMMIT DROP AS
SELECT
  v.codvalecompra,
  nextval('tblnegocio_codnegocio_seq')         AS codnegocio,
  nextval('tblnegociovale_codnegociovale_seq') AS codnegociovale,
  (v.inativo IS NOT NULL OR t.estornado IS NOT NULL) AS cancelado,
  (SELECT min(el.codestoquelocal) FROM tblestoquelocal el WHERE el.codfilial = v.codfilial) AS codestoquelocal
FROM tblvalecompra v
JOIN tbltitulo t ON (t.codtitulo = v.codtitulo);

CREATE TEMP TABLE _m9_pagamento ON COMMIT DROP AS
SELECT
  f.codvalecompraformapagamento,
  m.codnegocio,
  nextval('tblnegocioformapagamento_codnegocioformapagamento_seq') AS codnegocioformapagamento
FROM tblvalecompraformapagamento f
JOIN _m9_vale m ON (m.codvalecompra = f.codvalecompra);

-- ---------------------------------------------------------------------
-- 2) Negocio: o recibo da venda do vale
-- ---------------------------------------------------------------------

INSERT INTO tblnegocio (
  codnegocio, codpessoa, codfilial, codestoquelocal, lancamento,
  codoperacao, codnaturezaoperacao, codnegociostatus, codusuario,
  observacoes, justificativa,
  valorprodutos, valorvales, valordesconto, valortotal,
  valoravista, valoraprazo,
  criacao, codusuariocriacao, alteracao, codusuarioalteracao
)
SELECT
  m.codnegocio,
  v.codpessoa,
  v.codfilial,
  m.codestoquelocal,
  v.criacao,
  2,   -- saida
  1,   -- Venda (decisao 3b: natureza de venda normal)
  CASE WHEN m.cancelado THEN 3 ELSE 2 END,
  v.codusuariocriacao,
  'Vale Compras V' || lpad(v.codvalecompra::text, 8, '0')
    || ' convertido do sistema antigo.'
    || CASE WHEN v.codvalecompra = 2348
         THEN ' No sistema antigo este vale ficou com dois pagamentos de 131,82 (dinheiro e crediario); mantidos como estavam.'
         ELSE '' END,
  CASE
    WHEN v.inativo IS NOT NULL THEN 'Vale cancelado no sistema antigo'
    WHEN m.cancelado           THEN 'Credito do vale estornado no sistema antigo'
  END,
  0,
  v.total,
  0,
  v.total,
  coalesce((SELECT sum(f.valorpagamento) FROM tblvalecompraformapagamento f
             JOIN tblformapagamento fp ON (fp.codformapagamento = f.codformapagamento)
            WHERE f.codvalecompra = v.codvalecompra AND fp.avista), 0),
  coalesce((SELECT sum(f.valorpagamento) FROM tblvalecompraformapagamento f
             JOIN tblformapagamento fp ON (fp.codformapagamento = f.codformapagamento)
            WHERE f.codvalecompra = v.codvalecompra AND NOT fp.avista), 0),
  v.criacao, v.codusuariocriacao, v.alteracao, v.codusuarioalteracao
FROM tblvalecompra v
JOIN _m9_vale m ON (m.codvalecompra = v.codvalecompra);

-- ---------------------------------------------------------------------
-- 3) Pagamentos: um por pagamento antigo (1:N -- ver o vale 2348)
--
-- "tipo" e' o tPag, no mesmo mapa que o PDV grava hoje para cada forma.
-- E' por ele que o relatorio DIMP separa cartao/PIX do resto. Cartao do
-- sistema antigo nao sabia credito x debito: vai como credito (3). A forma
-- 1030 (Vale) e' um vale pago com outro vale -- o saldo daquele credito ja'
-- foi baixado no sistema antigo, entao aqui NAO ha' codtitulo (que faria o
-- cancelamento estornar uma baixa que nao e' deste registro).
-- ---------------------------------------------------------------------

INSERT INTO tblnegocioformapagamento (
  codnegocioformapagamento, codnegocio, codformapagamento,
  valorpagamento, valortotal, parcelas, valorparcela,
  avista, tipo, integracao,
  criacao, codusuariocriacao, alteracao, codusuarioalteracao
)
SELECT
  mp.codnegocioformapagamento,
  mp.codnegocio,
  f.codformapagamento,
  f.valorpagamento,
  f.valorpagamento,
  1,
  f.valorpagamento,
  fp.avista,
  CASE f.codformapagamento
    WHEN 1010 THEN 1    -- dinheiro
    WHEN 1020 THEN 2    -- cheque
    WHEN 1030 THEN 12   -- vale
    WHEN 2010 THEN 3    -- cartao (credito)
    WHEN 5605 THEN 3    -- PagarMe
    WHEN 4100 THEN 15   -- boleto
    WHEN 5604 THEN 17   -- PIX QR
    WHEN 5606 THEN 16   -- PIX chave / deposito
    ELSE 5              -- crediario, fechamento, entrega a vista
  END,
  false,
  f.criacao, f.codusuariocriacao, f.alteracao, f.codusuarioalteracao
FROM tblvalecompraformapagamento f
JOIN _m9_pagamento mp ON (mp.codvalecompraformapagamento = f.codvalecompraformapagamento)
JOIN tblformapagamento fp ON (fp.codformapagamento = f.codformapagamento);

-- ---------------------------------------------------------------------
-- 4) O vale, apontando para o MESMO titulo de credito
-- ---------------------------------------------------------------------

INSERT INTO tblnegociovale (
  codnegociovale, codnegocio, codtitulo, codpessoafavorecido,
  aluno, turma, codvalemodelo,
  valorprodutos, valoravulso, valorvale, valordesconto, valorjuros, valortotal,
  validade, codvalecompra, observacoes, inativo,
  criacao, codusuariocriacao, alteracao, codusuarioalteracao
)
SELECT
  m.codnegociovale,
  m.codnegocio,
  v.codtitulo,
  v.codpessoafavorecido,
  v.aluno,
  nullif(btrim(v.turma), ''),
  v.codvalecompramodelo,
  v.totalprodutos,
  -coalesce(v.desconto, 0),   -- desconto antigo = avulso negativo
  v.total,                    -- = credito do titulo
  NULL,
  NULL,
  v.total,
  t.vencimento,
  v.codvalecompra,
  v.observacoes,
  NULL,
  v.criacao, v.codusuariocriacao, v.alteracao, v.codusuarioalteracao
FROM tblvalecompra v
JOIN _m9_vale m ON (m.codvalecompra = v.codvalecompra)
JOIN tbltitulo t ON (t.codtitulo = v.codtitulo);

-- ---------------------------------------------------------------------
-- 5) Itens do kit. O antigo nao tinha ordenacao; a ordem de insercao
--    (PK) vira um segundo a mais por item, para a grade sair igual.
-- ---------------------------------------------------------------------

INSERT INTO tblnegociovaleprodutobarra (
  codnegociovale, codprodutobarra, quantidade, valorunitario, valorprodutos,
  ordenacao,
  criacao, codusuariocriacao, alteracao, codusuarioalteracao
)
SELECT
  m.codnegociovale,
  p.codprodutobarra,
  p.quantidade,
  p.preco,
  p.total,
  coalesce(p.criacao, v.criacao)
    + (row_number() OVER (PARTITION BY p.codvalecompra ORDER BY p.codvalecompraprodutobarra) - 1)
      * interval '1 second',
  coalesce(p.criacao, v.criacao), p.codusuariocriacao,
  coalesce(p.alteracao, v.alteracao), p.codusuarioalteracao
FROM tblvalecompraprodutobarra p
JOIN tblvalecompra v ON (v.codvalecompra = p.codvalecompra)
JOIN _m9_vale m ON (m.codvalecompra = p.codvalecompra);

-- ---------------------------------------------------------------------
-- 6) Os titulos a prazo (tipo 240) passam a pender do pagamento novo
-- ---------------------------------------------------------------------

SELECT count(*), sum(debito), sum(saldo) INTO v_tit_qtd, v_tit_deb, v_tit_sal
  FROM tbltitulo WHERE codvalecompraformapagamento IS NOT NULL;

UPDATE tbltitulo t
   SET codnegocioformapagamento = mp.codnegocioformapagamento,
       codvalecompraformapagamento = NULL
  FROM _m9_pagamento mp
 WHERE t.codvalecompraformapagamento = mp.codvalecompraformapagamento;

-- ---------------------------------------------------------------------
-- 7) Conferencias. Qualquer uma que nao feche aborta TUDO, antes do DROP.
-- ---------------------------------------------------------------------

-- vales
SELECT count(*), sum(total) INTO v_qtd, v_soma FROM tblvalecompra;
SELECT count(*), sum(valorvale) INTO v_qtd2, v_soma2
  FROM tblnegociovale WHERE codvalecompra IS NOT NULL;
IF v_qtd <> v_qtd2 OR v_soma <> v_soma2 THEN
  RAISE EXCEPTION 'conferencia VALES: legado % / %, convertido % / %', v_qtd, v_soma, v_qtd2, v_soma2;
END IF;

-- valorvale = produtos + avulso = credito do titulo, vale a vale
SELECT count(*) INTO v_qtd
  FROM tblnegociovale nv
  JOIN tbltitulo t ON (t.codtitulo = nv.codtitulo)
 WHERE nv.codvalecompra IS NOT NULL
   AND (nv.valorvale <> nv.valorprodutos + nv.valoravulso
        OR nv.valorvale <> round(coalesce(t.credito, 0) - coalesce(t.debito, 0), 2));
IF v_qtd <> 0 THEN
  RAISE EXCEPTION 'conferencia FACE x CREDITO: % vales divergem', v_qtd;
END IF;

-- negocios
SELECT count(*), sum(n.valortotal),
       count(*) FILTER (WHERE n.codnegociostatus = 3)
  INTO v_qtd, v_soma, v_qtd2
  FROM tblnegocio n
  JOIN _m9_vale m ON (m.codnegocio = n.codnegocio);
SELECT sum(total) INTO v_soma2 FROM tblvalecompra;
SELECT count(*) INTO v_soma3 FROM _m9_vale WHERE cancelado;
IF v_qtd <> (SELECT count(*) FROM tblvalecompra) OR v_soma <> v_soma2 OR v_qtd2 <> v_soma3 THEN
  RAISE EXCEPTION 'conferencia NEGOCIOS: % negocios, total %, % cancelados', v_qtd, v_soma, v_qtd2;
END IF;

-- itens
SELECT count(*), sum(total) INTO v_qtd, v_soma FROM tblvalecompraprodutobarra;
SELECT count(*), sum(nvpb.valorprodutos) INTO v_qtd2, v_soma2
  FROM tblnegociovaleprodutobarra nvpb
  JOIN tblnegociovale nv ON (nv.codnegociovale = nvpb.codnegociovale)
 WHERE nv.codvalecompra IS NOT NULL;
IF v_qtd <> v_qtd2 OR v_soma <> v_soma2 THEN
  RAISE EXCEPTION 'conferencia ITENS: legado % / %, convertido % / %', v_qtd, v_soma, v_qtd2, v_soma2;
END IF;

-- pagamentos, forma a forma
SELECT count(*) INTO v_qtd FROM (
  SELECT codformapagamento, count(*) AS q, sum(valorpagamento) AS s
    FROM tblvalecompraformapagamento GROUP BY 1
  EXCEPT
  SELECT nfp.codformapagamento, count(*), sum(nfp.valorpagamento)
    FROM tblnegocioformapagamento nfp
    JOIN _m9_vale m ON (m.codnegocio = nfp.codnegocio)
   GROUP BY 1
) d;
IF v_qtd <> 0 THEN
  RAISE EXCEPTION 'conferencia PAGAMENTOS: % formas divergem', v_qtd;
END IF;

-- titulos a prazo
SELECT count(*) INTO v_qtd FROM tbltitulo WHERE codvalecompraformapagamento IS NOT NULL;
SELECT count(*), sum(t.debito), sum(t.saldo) INTO v_qtd2, v_soma, v_soma2
  FROM tbltitulo t
  JOIN _m9_pagamento mp ON (mp.codnegocioformapagamento = t.codnegocioformapagamento);
IF v_qtd <> 0 OR v_qtd2 <> v_tit_qtd OR v_soma <> v_tit_deb OR v_soma2 <> v_tit_sal THEN
  RAISE EXCEPTION 'conferencia TITULOS A PRAZO: antes % / % / %, depois % / % / %, % ainda no pagamento antigo',
    v_tit_qtd, v_tit_deb, v_tit_sal, v_qtd2, v_soma, v_soma2, v_qtd;
END IF;

-- ---------------------------------------------------------------------
-- 8) Limpeza: o legado deixa de existir
-- ---------------------------------------------------------------------

ALTER TABLE tbltitulo DROP COLUMN codvalecompraformapagamento;
DROP TABLE tblvalecompraprodutobarra;
DROP TABLE tblvalecompraformapagamento;
DROP TABLE tblvalecompra;
ALTER TABLE tblformapagamento DROP COLUMN IF EXISTS valecompra;

SELECT count(*) INTO v_qtd FROM _m9_vale;
RAISE NOTICE 'vale_conversao.sql: % vales convertidos, legado removido.', v_qtd;

END $$;

COMMIT;

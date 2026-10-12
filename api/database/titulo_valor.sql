-- =====================================================================
-- Titulos: valor/saldo com sinal, sem triggers (TASK-186, milestone M0.1
-- do plano backlog/docs/doc-3).
--
-- O script cobre os dois milestones de titulos: M0.1 (secoes 1 a 3, valor
-- com sinal e fim das triggers) e M0.2 (secao 4, catalogos).
--
-- O QUE MUDA
--   tbltitulo.valor            — valor do titulo COM SINAL
--   tblmovimentotitulo.valor   — valor do movimento COM SINAL
--   tbltituloagrupamento.valor — total do agrupamento COM SINAL
--   tblliquidacaotitulo.valor  — total LIQUIDO da liquidacao COM SINAL
--                                (recebeu mais do que pagou = negativo,
--                                como o movimento que baixa o titulo)
--   tblmovimentotitulo.codmovimentotituloestorno
--                              — no estorno, aponta para o movimento
--                                original (auto-FK)
--   as 4 triggers do sistema antigo deixam de existir
--   tbltipotitulo.natureza     — R (a receber) ou P (a pagar): o sinal com
--                                que o titulo nasce. Entra no lugar dos
--                                flags debito/credito
--   tbltipotitulo.movimentaportador
--                              — a implantacao ja' tira/poe dinheiro no
--                                portador (adiantamentos, creditos, vale)
--   tipos de titulo e de movimento sem uso ficam inativos; os 7 flags do
--   tipo de movimento e o tipo de movimento do tipo de titulo saem
--   a coluna "sistema" sai de titulo, movimento e liquidacao: era a data de
--   gravacao do sistema antigo, e quem guarda isso e' criacao/alteracao
--
-- O SINAL: positivo = a receber (o antigo debito), negativo = a pagar (o
-- antigo credito). valor = debito - credito, sempre. tbltitulo.saldo ja'
-- era assim e nao muda.
--
-- AS TRIGGERS que saem, e quem passa a fazer o servico delas:
--   fntbltituloai              implantacao ao inserir titulo
--                              -> TituloService::implantar()
--   fntbltituloau              ajuste (200) ao alterar debito/credito
--                              -> TituloService::atualizar()
--   fntblmovimentotituloaiauad saldo/estornado/transacaoliquidacao do
--                              titulo e total da liquidacao
--                              -> MovimentoTituloService::recalcular()
--   fntbltituloaiauad          total do agrupamento
--                              -> MovimentoTituloService::recalcular()
--
-- DEPLOY: este script e o codigo novo sobem NA MESMA JANELA, com a API
-- parada. Um sem o outro quebra dos dois lados:
--   - codigo novo sem o script: grava em coluna que nao existe, e com a
--     trigger ainda viva a implantacao nasce em dobro;
--   - script sem o codigo novo: titulo nasce sem implantacao e sem saldo.
--
-- QUEM MAIS GRAVA TITULO: o MGsis (usuario mgsis_yii), na NFe de Terceiros
-- (importacao e guia de ICMS ST) — a unica tela dele ainda em uso. Ele
-- dependia da fntblmovimentotituloaiauad para o saldo e grava o valor sem
-- sinal. Os models Titulo e MovimentoTitulo de la' foram ajustados para
-- gravar valor com sinal e recalcular o saldo: SOBEM JUNTO com este script.
-- As demais telas de titulo do MGsis (menu desligado) nao foram ajustadas.
--
-- LIMPEZA (secao 3): caem as 4 views e as colunas antigas de titulo,
-- movimento e agrupamento. NAO caem debito/credito da LIQUIDACAO: o "Totais
-- de Caixa" do MGLara (CaixaController) soma essas duas colunas e e' o que
-- o caixa usa hoje para fechar. Elas saem quando essa tela sair; ate' la' o
-- MovimentoTituloService::recalcularLiquidacao grava as duas junto com valor.
--
-- DUAS TRANSACOES, de proposito:
--   1) estrutura — ADD COLUMN e DROP TRIGGER pegam ACCESS EXCLUSIVE, mas
--      duram milissegundos;
--   2) backfill — 780 mil titulos e 1,7 milhao de movimentos. Separado
--      para nao segurar o ACCESS EXCLUSIVE da estrutura durante minutos.
-- E uma terceira para a limpeza, que so' roda depois do backfill completo.
-- As triggers caem ANTES do backfill: com elas vivas, cada linha de
-- movimento atualizada recalcularia o titulo inteiro.
--
-- Idempotente (ADD COLUMN IF NOT EXISTS, DROP ... IF EXISTS, guards em
-- pg_constraint e information_schema; o backfill so' toca valor nulo).
-- Pode rodar de novo a vontade.
-- =====================================================================

\set ON_ERROR_STOP on

-- ---------------------------------------------------------------------
-- 1) Estrutura
-- ---------------------------------------------------------------------
BEGIN;

-- tbltitulo e tblmovimentotitulo sao tabelas quentes. Se alguem estiver
-- com transacao ociosa segurando lock nelas, este script ficaria na fila
-- travando o financeiro e o PDV. Com o lock_timeout ele desiste em 5s e
-- faz rollback. Nunca remover estes dois SET.
SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '60s';

ALTER TABLE tbltitulo          ADD COLUMN IF NOT EXISTS valor numeric(14,2);
ALTER TABLE tblmovimentotitulo ADD COLUMN IF NOT EXISTS valor numeric(14,2);
ALTER TABLE tblmovimentotitulo ADD COLUMN IF NOT EXISTS codmovimentotituloestorno bigint;
ALTER TABLE tbltituloagrupamento ADD COLUMN IF NOT EXISTS valor numeric(14,2);
ALTER TABLE tblliquidacaotitulo  ADD COLUMN IF NOT EXISTS valor numeric(14,2);

DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_constraint
                 WHERE conname = 'tblmovimentotitulo_codmovimentotituloestorno_fkey'
                   AND conrelid = to_regclass('tblmovimentotitulo')) THEN
    ALTER TABLE tblmovimentotitulo
      ADD CONSTRAINT tblmovimentotitulo_codmovimentotituloestorno_fkey
      FOREIGN KEY (codmovimentotituloestorno)
      REFERENCES tblmovimentotitulo(codmovimentotitulo) ON UPDATE CASCADE;
  END IF;
END $$;

CREATE INDEX IF NOT EXISTS tblmovimentotitulo_codmovimentotituloestorno_idx
  ON tblmovimentotitulo (codmovimentotituloestorno)
  WHERE codmovimentotituloestorno IS NOT NULL;

DROP TRIGGER IF EXISTS tbltituloai              ON tbltitulo;
DROP TRIGGER IF EXISTS tbltituloau              ON tbltitulo;
DROP TRIGGER IF EXISTS tbltituloaiauad          ON tbltitulo;
DROP TRIGGER IF EXISTS tblmovimentotituloaiauad ON tblmovimentotitulo;

DROP FUNCTION IF EXISTS fntbltituloai();
DROP FUNCTION IF EXISTS fntbltituloau();
DROP FUNCTION IF EXISTS fntbltituloaiauad();
DROP FUNCTION IF EXISTS fntblmovimentotituloaiauad();

COMMIT;

-- ---------------------------------------------------------------------
-- 2) Backfill: valor = debito - credito
--
-- So' toca linha com valor nulo, entao rodar de novo nao reescreve nada.
-- Dentro de DO + EXECUTE porque, depois da limpeza, debito/credito deixam
-- de existir e o script tem que continuar rodando.
-- ---------------------------------------------------------------------
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '30min';

DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns
             WHERE table_schema = current_schema()
               AND table_name = 'tbltitulo' AND column_name = 'debito') THEN
    EXECUTE '
      UPDATE tbltitulo
         SET valor = coalesce(debito, 0) - coalesce(credito, 0)
       WHERE valor IS NULL';
  END IF;

  IF EXISTS (SELECT 1 FROM information_schema.columns
             WHERE table_schema = current_schema()
               AND table_name = 'tblmovimentotitulo' AND column_name = 'debito') THEN
    EXECUTE '
      UPDATE tblmovimentotitulo
         SET valor = coalesce(debito, 0) - coalesce(credito, 0)
       WHERE valor IS NULL';
  END IF;

  IF EXISTS (SELECT 1 FROM information_schema.columns
             WHERE table_schema = current_schema()
               AND table_name = 'tbltituloagrupamento' AND column_name = 'debito') THEN
    EXECUTE '
      UPDATE tbltituloagrupamento
         SET valor = coalesce(debito, 0) - coalesce(credito, 0)
       WHERE valor IS NULL';
  END IF;

  IF EXISTS (SELECT 1 FROM information_schema.columns
             WHERE table_schema = current_schema()
               AND table_name = 'tblliquidacaotitulo' AND column_name = 'debito') THEN
    EXECUTE '
      UPDATE tblliquidacaotitulo
         SET valor = coalesce(debito, 0) - coalesce(credito, 0)
       WHERE valor IS NULL';
  END IF;
END $$;

COMMIT;

-- ---------------------------------------------------------------------
-- 3) Limpeza: views e colunas antigas
--
-- Trava de seguranca: se sobrou linha sem valor, o backfill nao fechou e
-- derrubar debito/credito perderia o dado. Aborta tudo.
-- ---------------------------------------------------------------------
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '60s';

DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM tbltitulo WHERE valor IS NULL)
     OR EXISTS (SELECT 1 FROM tblmovimentotitulo WHERE valor IS NULL)
     OR EXISTS (SELECT 1 FROM tbltituloagrupamento WHERE valor IS NULL) THEN
    RAISE EXCEPTION 'Ha linha sem valor: o backfill nao fechou. Limpeza abortada.';
  END IF;
END $$;

DROP VIEW IF EXISTS vwboleto;
DROP VIEW IF EXISTS vwliquidacaotitulo;
DROP VIEW IF EXISTS vwtitulo;
DROP VIEW IF EXISTS vwtituloagrupamento_baixados;

ALTER TABLE tbltitulo
  DROP COLUMN IF EXISTS debito,
  DROP COLUMN IF EXISTS credito,
  DROP COLUMN IF EXISTS debitototal,
  DROP COLUMN IF EXISTS creditototal,
  DROP COLUMN IF EXISTS debitosaldo,
  DROP COLUMN IF EXISTS creditosaldo;

ALTER TABLE tblmovimentotitulo
  DROP COLUMN IF EXISTS debito,
  DROP COLUMN IF EXISTS credito;

ALTER TABLE tbltituloagrupamento
  DROP COLUMN IF EXISTS debito,
  DROP COLUMN IF EXISTS credito;

COMMIT;

-- ---------------------------------------------------------------------
-- 4) Catalogos (M0.2)
--
-- natureza e' o SINAL (o que os flags debito/credito diziam), nao a
-- carteira: "Adto Fornecedor" e' da carteira de fornecedores (pagar = true)
-- mas e' dinheiro que o fornecedor nos deve, entao nasce positivo (R). Por
-- isso pagar/receber FICAM — sao a carteira, e o filtro "Pagar / Receber"
-- da listagem de titulos usa os dois.
--
-- Tres tipos tinham os flags debito/credito ambiguos (os dois ligados ou
-- nenhum). Para eles vale o sinal dos titulos que existem:
--   932 Permuta     -> P (110 titulos, todos negativos)
--   948 Garantia    -> R (17 titulos, todos positivos)
--   952 Rubrica RH  -> P (363 de 364 negativos)
--
-- movimentaportador: os 4 tipos cuja implantacao era lancada como
-- liquidacao (600) — Adto/Credito de Fornecedor e de Cliente — mais o Vale
-- Colaborador. Daqui pra frente toda implantacao e' tipo 100.
--
-- INATIVOS: os tipos de titulo sem nenhum titulo desde 01/01/2025. Todas as
-- naturezas de operacao que apontam para eles estao com financeiro
-- desligado (nao geram titulo). A excecao e' o 946 Remessa Armazenagem, que
-- FICA ATIVO: nunca teve titulo, mas a natureza 72 (Fixacao de Preco, ato
-- cooperativo) aponta para ele com financeiro ligado.
-- Rodar o script de novo inativa outra vez um tipo que tenha sido reativado.
-- ---------------------------------------------------------------------
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '60s';

ALTER TABLE tbltipotitulo ADD COLUMN IF NOT EXISTS natureza char(1);
ALTER TABLE tbltipotitulo ADD COLUMN IF NOT EXISTS movimentaportador boolean NOT NULL DEFAULT false;

DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns
             WHERE table_schema = current_schema()
               AND table_name = 'tbltipotitulo' AND column_name = 'credito') THEN
    EXECUTE '
      UPDATE tbltipotitulo
         SET natureza = CASE WHEN credito AND NOT debito THEN ''P''
                             WHEN debito AND NOT credito THEN ''R''
                        END
       WHERE natureza IS NULL';
  END IF;

  UPDATE tbltipotitulo SET natureza = 'P' WHERE natureza IS NULL AND codtipotitulo IN (932, 952);
  UPDATE tbltipotitulo SET natureza = 'R' WHERE natureza IS NULL AND codtipotitulo = 948;

  IF EXISTS (SELECT 1 FROM tbltipotitulo WHERE natureza IS NULL) THEN
    RAISE EXCEPTION 'Ha tipo de titulo sem natureza: decidir o sinal dele antes de seguir.';
  END IF;

  IF EXISTS (SELECT 1 FROM information_schema.columns
             WHERE table_schema = current_schema()
               AND table_name = 'tbltipotitulo' AND column_name = 'codtipomovimentotitulo') THEN
    EXECUTE '
      UPDATE tbltipotitulo
         SET movimentaportador = true
       WHERE codtipomovimentotitulo = 600 OR codtipotitulo = 2';
  END IF;

  IF NOT EXISTS (SELECT 1 FROM pg_constraint
                 WHERE conname = 'tbltipotitulo_natureza_check'
                   AND conrelid = to_regclass('tbltipotitulo')) THEN
    ALTER TABLE tbltipotitulo
      ADD CONSTRAINT tbltipotitulo_natureza_check CHECK (natureza IN ('R', 'P'));
  END IF;
END $$;

ALTER TABLE tbltipotitulo ALTER COLUMN natureza SET NOT NULL;

UPDATE tbltipotitulo
   SET tipotitulo = 'Vale Colaborador'
 WHERE codtipotitulo = 2 AND tipotitulo = 'Vale Funcionario';

-- (Repasse Parceiro, 953, saiu na limpeza dos tipos do M8.1: o repasse do
-- M13 e' uma Duplicata a Pagar.)

SELECT setval('tbltipotitulo_codtipotitulo_seq', (SELECT max(codtipotitulo) FROM tbltipotitulo));

UPDATE tbltipotitulo
   SET inativo = now()
 WHERE inativo IS NULL
   AND codtipotitulo IN (5, 6, 8, 130, 922, 923, 924, 925, 926, 929, 932, 933, 934,
                         936, 939, 940, 941, 942, 943, 944, 947, 948, 949);

UPDATE tbltipomovimentotitulo
   SET inativo = now()
 WHERE inativo IS NULL
   AND codtipomovimentotitulo IN (610, 910, 920, 992, 993);

ALTER TABLE tbltipomovimentotitulo
  DROP COLUMN IF EXISTS implantacao,
  DROP COLUMN IF EXISTS ajuste,
  DROP COLUMN IF EXISTS armotizacao,
  DROP COLUMN IF EXISTS juros,
  DROP COLUMN IF EXISTS desconto,
  DROP COLUMN IF EXISTS pagamento,
  DROP COLUMN IF EXISTS estorno;

ALTER TABLE tbltipotitulo
  DROP COLUMN IF EXISTS codtipomovimentotitulo,
  DROP COLUMN IF EXISTS debito,
  DROP COLUMN IF EXISTS credito;

COMMIT;

-- ---------------------------------------------------------------------
-- 5) Coluna "sistema"
--
-- Linha antiga tem sistema preenchido e criacao em branco (121 mil titulos
-- e 342 mil movimentos): a data vai para criacao antes de a coluna cair.
-- alteracao em branco NAO e' preenchida — fica como esta'.
-- Trava de seguranca: so' derruba se nao sobrou criacao em branco com
-- sistema preenchido.
-- ---------------------------------------------------------------------
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '30min';

DO $$
DECLARE
  t text;
  sobrou bigint;
BEGIN
  FOREACH t IN ARRAY ARRAY['tbltitulo', 'tblmovimentotitulo', 'tblliquidacaotitulo'] LOOP
    IF EXISTS (SELECT 1 FROM information_schema.columns
               WHERE table_schema = current_schema()
                 AND table_name = t AND column_name = 'sistema') THEN
      EXECUTE format('UPDATE %I SET criacao = sistema WHERE criacao IS NULL AND sistema IS NOT NULL', t);
      EXECUTE format('SELECT count(*) FROM %I WHERE criacao IS NULL AND sistema IS NOT NULL', t) INTO sobrou;
      IF sobrou > 0 THEN
        RAISE EXCEPTION 'Sobrou criacao em branco em %: a coluna sistema nao pode cair.', t;
      END IF;
      EXECUTE format('ALTER TABLE %I DROP COLUMN sistema', t);
    END IF;
  END LOOP;
END $$;

COMMIT;

-- O backfill deixou uma versao morta de cada linha; sem isto as duas
-- tabelas ficam com o dobro do tamanho ate' o autovacuum passar.
-- PARALLEL 0: o vacuum paralelo dos indices pede memoria compartilhada, e
-- o /dev/shm de 64MB do container nao da' ("could not resize shared
-- memory segment").
VACUUM (ANALYZE, PARALLEL 0) tbltitulo;
VACUUM (ANALYZE, PARALLEL 0) tblmovimentotitulo;
VACUUM (ANALYZE, PARALLEL 0) tblliquidacaotitulo;

-- ---------------------------------------------------------------------
-- Conferencia
-- ---------------------------------------------------------------------
\echo '== colunas novas =='
SELECT table_name, column_name, data_type, is_nullable
FROM information_schema.columns
WHERE (table_name IN ('tbltitulo', 'tbltituloagrupamento', 'tblliquidacaotitulo') AND column_name = 'valor')
   OR (table_name = 'tblmovimentotitulo' AND column_name IN ('valor', 'codmovimentotituloestorno'))
ORDER BY 1, 2;

\echo '== triggers que sobraram em tbltitulo/tblmovimentotitulo (esperado: nenhuma) =='
SELECT c.relname AS tabela, t.tgname
FROM pg_trigger t
JOIN pg_class c ON c.oid = t.tgrelid
WHERE NOT t.tgisinternal
  AND c.relname IN ('tbltitulo', 'tblmovimentotitulo');

\echo '== colunas antigas que sobraram (esperado: so debito e credito de tblliquidacaotitulo) =='
SELECT table_name, column_name
FROM information_schema.columns
WHERE table_name IN ('tbltitulo', 'tblmovimentotitulo', 'tbltituloagrupamento', 'tblliquidacaotitulo')
  AND column_name IN ('debito', 'credito', 'debitototal', 'creditototal', 'debitosaldo', 'creditosaldo')
ORDER BY 1, 2;

\echo '== views que sobraram (esperado: nenhuma) =='
SELECT relname FROM pg_class
WHERE relname IN ('vwboleto', 'vwliquidacaotitulo', 'vwtitulo', 'vwtituloagrupamento_baixados');

\echo '== tipos de titulo (natureza, movimentaportador, inativos) =='
SELECT natureza,
       count(*) AS tipos,
       count(*) FILTER (WHERE inativo IS NULL) AS ativos,
       count(*) FILTER (WHERE movimentaportador) AS movimentam_portador
FROM tbltipotitulo
GROUP BY natureza
ORDER BY natureza;

\echo '== tipos de movimento inativos (esperado: 610, 910, 920, 992, 993) =='
SELECT codtipomovimentotitulo, tipomovimentotitulo
FROM tbltipomovimentotitulo
WHERE inativo IS NOT NULL
ORDER BY 1;

\echo '== coluna sistema que sobrou (esperado: nenhuma) e linhas sem criacao (esperado: tudo 0) =='
SELECT table_name FROM information_schema.columns
WHERE table_name IN ('tbltitulo', 'tblmovimentotitulo', 'tblliquidacaotitulo') AND column_name = 'sistema';
SELECT (SELECT count(*) FROM tbltitulo WHERE criacao IS NULL)           AS titulos_sem_criacao,
       (SELECT count(*) FROM tblmovimentotitulo WHERE criacao IS NULL)  AS movimentos_sem_criacao,
       (SELECT count(*) FROM tblliquidacaotitulo WHERE criacao IS NULL) AS liquidacoes_sem_criacao;

\echo '== linhas sem valor (esperado: tudo 0) =='
SELECT (SELECT count(*) FROM tbltitulo WHERE valor IS NULL)            AS titulos_sem_valor,
       (SELECT count(*) FROM tblmovimentotitulo WHERE valor IS NULL)   AS movimentos_sem_valor,
       (SELECT count(*) FROM tbltituloagrupamento WHERE valor IS NULL) AS agrupamentos_sem_valor,
       (SELECT count(*) FROM tblliquidacaotitulo WHERE valor IS NULL)  AS liquidacoes_sem_valor;

-- =====================================================================
-- Tipos de titulo enxutos e renumerados (M8.1 do plano doc-3, decidido
-- com o Fabio em 02/10/2026; segunda passada depois da TASK-186).
--
-- Ficam 14 tipos, com codigo de 3 digitos: 1xx a receber, 2xx a pagar.
-- Somem 15 tipos (os titulos passam para um tipo da mesma natureza, entao
-- o sinal nao muda): agrupamento debito/credito, debito cliente, os de
-- forma de pagar (boleto, CTRC, programacao, debito automatico), compra,
-- compra/venda imovel, entrada bonificacao, outras saidas, remessa
-- armazenagem e repasse parceiro. O Vale Compras de devolucao (conta
-- "Devolucao de Vendas") vira Credito Cliente, e as naturezas de devolucao
-- de venda passam a gerar Credito Cliente. Credito Cliente nao movimenta
-- portador (so' vale colaborador e os adiantamentos).
--
-- Renumerar: as FKs de tbltitulo e tblnaturezaoperacao para tbltipotitulo
-- sao ON UPDATE CASCADE; em duas passadas (antigo -> 10000+novo -> novo),
-- porque codigos novos e antigos se cruzam (100, 120, 200, 201, 220).
--
-- Ultimo script do go-live: os anteriores usam os codigos antigos. O
-- MGsis (NfeTerceiroController) sobe junto, gravando 200.
--
-- Parte 2 (mais abaixo): os 23 tipos que a TASK-186 tinha inativado. Os
-- titulos vao para um tipo ativo e o tipo e' apagado; ficam inativos, so'
-- de historico, 130 Transferencia Saida, 131 Uso e Consumo, 132 Perda, 133
-- Doacao, Brinde e 230 Transferencia Entrada. Reativa o portador Barter e
-- cria o Permuta.
--
-- Idempotente: cada parte so' executa enquanto um tipo antigo dela existir
-- (927 na 1, 936 na 2); depois, so' confere. Transacional. Aborta se nao
-- fechar.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '15min';

-- nomes de ate' 50 (Adiantamento Fornecedor nao cabe em 20)
ALTER TABLE tbltipotitulo ALTER COLUMN tipotitulo TYPE varchar(50);

DO $$
DECLARE
    v_qtd integer;
    v_dif integer;
    r record;
BEGIN
    IF NOT EXISTS (SELECT 1 FROM tbltipotitulo WHERE codtipotitulo = 927) THEN
        RAISE NOTICE 'Tipos de titulo ja reorganizados: so conferencia.';
        RETURN;
    END IF;

    -- conferencia antes: saldo e valor de cada titulo, natureza do tipo
    CREATE TEMP TABLE tl_antes ON COMMIT DROP AS
    SELECT t.codtitulo, t.valor, t.saldo, tt.natureza
      FROM tbltitulo t JOIN tbltipotitulo tt USING (codtipotitulo);

    -- 1. o que some -> para onde vai (codigos antigos)
    CREATE TEMP TABLE tl_de_para (de bigint PRIMARY KEY, para bigint NOT NULL) ON COMMIT DROP;
    INSERT INTO tl_de_para VALUES
        (921, 200), (240, 200), (950, 200), (945, 200), (946, 200),  -- a receber
        (140, 4),
        (911, 927), (928, 927), (937, 927), (931, 927), (100, 927),  -- a pagar
        (935, 927), (951, 927), (7, 927), (953, 927);

    -- mesma natureza dos dois lados (o sinal do titulo nao muda); tipo sem
    -- titulo (946, cadastrado 'P' com flag de receber) nao importa
    SELECT count(*) INTO v_dif
      FROM tl_de_para dp
      JOIN tbltipotitulo a ON a.codtipotitulo = dp.de
      JOIN tbltipotitulo b ON b.codtipotitulo = dp.para
     WHERE a.natureza <> b.natureza
       AND EXISTS (SELECT 1 FROM tbltitulo t WHERE t.codtipotitulo = dp.de);
    IF v_dif > 0 THEN
        RAISE EXCEPTION 'Tipos: % pares de natureza diferente', v_dif;
    END IF;

    UPDATE tbltitulo t SET codtipotitulo = dp.para
      FROM tl_de_para dp WHERE t.codtipotitulo = dp.de;
    GET DIAGNOSTICS v_qtd = ROW_COUNT;
    RAISE NOTICE 'Titulos transferidos: %', v_qtd;

    UPDATE tblnaturezaoperacao n SET codtipotitulo = dp.para
      FROM tl_de_para dp WHERE n.codtipotitulo = dp.de;
    GET DIAGNOSTICS v_qtd = ROW_COUNT;
    RAISE NOTICE 'Naturezas de operacao reapontadas: %', v_qtd;

    -- vale compras de devolucao -> credito cliente; devolucao de venda
    -- passa a gerar credito cliente
    UPDATE tbltitulo SET codtipotitulo = 230
     WHERE codtipotitulo = 3 AND codcontacontabil = 8; -- Devolucao de Vendas
    GET DIAGNOSTICS v_qtd = ROW_COUNT;
    RAISE NOTICE 'Vale compras de devolucao -> credito cliente: %', v_qtd;

    UPDATE tblnaturezaoperacao SET codtipotitulo = 230 WHERE codtipotitulo = 3;
    GET DIAGNOSTICS v_qtd = ROW_COUNT;
    RAISE NOTICE 'Naturezas de devolucao de venda -> credito cliente: %', v_qtd;

    -- 2. apaga os que sumiram (ninguem mais aponta)
    DELETE FROM tbltipotitulo tt
     USING tl_de_para dp
     WHERE tt.codtipotitulo = dp.de
       AND NOT EXISTS (SELECT 1 FROM tbltitulo t WHERE t.codtipotitulo = tt.codtipotitulo)
       AND NOT EXISTS (SELECT 1 FROM tblnaturezaoperacao n WHERE n.codtipotitulo = tt.codtipotitulo);
    SELECT count(*) INTO v_dif FROM tbltipotitulo WHERE codtipotitulo IN (SELECT de FROM tl_de_para);
    IF v_dif > 0 THEN
        RAISE EXCEPTION 'Tipos: % tipos que sumiriam ainda tem referencia', v_dif;
    END IF;

    -- 3. renumera os 14 (nome e flag juntos)
    CREATE TEMP TABLE tl_novo (
        antigo bigint PRIMARY KEY, novo bigint NOT NULL, nome varchar(50) NOT NULL,
        movimenta boolean NOT NULL
    ) ON COMMIT DROP;
    INSERT INTO tl_novo VALUES
        (200, 100, 'Duplicata a Receber', false),
        (201, 101, 'PIX/Depósito Receber', false),
        (310, 102, 'Entrega Receber', false),
        (1,   111, 'Cheque Devolvido', false),
        (2,   120, 'Vale Colaborador', true),
        (120, 121, 'Adiantamento Fornecedor', true),
        (4,   122, 'Débito Fornecedor', false),
        (927, 200, 'Duplicata a Pagar', false),
        (930, 201, 'PIX/Depósito Pagar', false),
        (320, 202, 'Entrega Pagar', false),
        (3,   210, 'Vale Compras', false),
        (220, 211, 'Adiantamento Cliente', true),
        (230, 212, 'Crédito Cliente', false),
        (952, 220, 'Rubrica RH', false);

    -- os codigos novos nao podem ser de um tipo que fica com codigo antigo
    SELECT count(*) INTO v_dif
      FROM tl_novo n
      JOIN tbltipotitulo tt ON tt.codtipotitulo = n.novo
     WHERE tt.codtipotitulo NOT IN (SELECT antigo FROM tl_novo);
    IF v_dif > 0 THEN
        RAISE EXCEPTION 'Tipos: % codigos novos ja usados por tipo que fica', v_dif;
    END IF;

    FOR r IN SELECT * FROM tl_novo LOOP
        UPDATE tbltipotitulo SET codtipotitulo = 10000 + r.novo WHERE codtipotitulo = r.antigo;
    END LOOP;
    FOR r IN SELECT * FROM tl_novo LOOP
        UPDATE tbltipotitulo
           SET codtipotitulo = r.novo, tipotitulo = r.nome, movimentaportador = r.movimenta,
               inativo = NULL
         WHERE codtipotitulo = 10000 + r.novo;
    END LOOP;

    -- conferencia depois: mesmo valor e saldo em cada titulo, mesma natureza
    SELECT count(*) INTO v_dif
      FROM tl_antes a
      JOIN tbltitulo t USING (codtitulo)
      JOIN tbltipotitulo tt ON tt.codtipotitulo = t.codtipotitulo
     WHERE t.valor IS DISTINCT FROM a.valor
        OR t.saldo IS DISTINCT FROM a.saldo
        OR tt.natureza <> a.natureza;
    IF v_dif > 0 THEN
        RAISE EXCEPTION 'Tipos: % titulos mudaram de valor, saldo ou natureza', v_dif;
    END IF;
    SELECT count(*) INTO v_dif FROM tl_antes a WHERE NOT EXISTS (SELECT 1 FROM tbltitulo t WHERE t.codtitulo = a.codtitulo);
    IF v_dif > 0 THEN
        RAISE EXCEPTION 'Tipos: % titulos sumiram', v_dif;
    END IF;
    SELECT count(*) INTO v_dif FROM tl_novo n WHERE NOT EXISTS (SELECT 1 FROM tbltipotitulo tt WHERE tt.codtipotitulo = n.novo);
    IF v_dif > 0 THEN
        RAISE EXCEPTION 'Tipos: % tipos novos faltando', v_dif;
    END IF;
END $$;

-- ---------------------------------------------------------------------
-- Parte 2: os inativos (TASK-186 inativou 23 em 28/09). Decidido um a um com
-- o Fabio em 02/10/2026: nada inativo "com lixo". Os titulos vao para um tipo
-- ativo (alguns com o nome antigo na frente da observacao, outros com o
-- portador Barter/Permuta) e o tipo e' apagado. Ficam inativos, renumerados,
-- so' os de historico de notas que nunca deviam ter gerado titulo:
-- 130 Transferencia Saida, 131 Uso e Consumo, 132 Perda, 133 Doacao, Brinde
-- e 230 Transferencia Entrada.
-- Idempotente: so' executa enquanto o tipo antigo 936 existir.
-- ---------------------------------------------------------------------

-- portadores da troca: Barter (fazenda, lista do que entrega e recebe ja'
-- definida) e Permuta (comercio, ex.: anuncio na radio pago em produtos)
UPDATE tblportador SET inativo = NULL WHERE codportador = 202007 AND inativo IS NOT NULL;
INSERT INTO tblportador (portador, tipo, emiteboleto)
SELECT 'Permuta', 'O', false
 WHERE NOT EXISTS (SELECT 1 FROM tblportador WHERE portador = 'Permuta');

DO $$
DECLARE
    v_qtd integer;
    v_dif integer;
    v_permuta bigint;
    r record;
BEGIN
    IF NOT EXISTS (SELECT 1 FROM tbltipotitulo WHERE codtipotitulo = 936) THEN
        RAISE NOTICE 'Tipos inativos ja reorganizados: so conferencia.';
        RETURN;
    END IF;
    SELECT codportador INTO v_permuta FROM tblportador WHERE portador = 'Permuta';

    CREATE TEMP TABLE tl2_antes ON COMMIT DROP AS
    SELECT t.codtitulo, t.valor, t.saldo, tt.natureza
      FROM tbltitulo t JOIN tbltipotitulo tt USING (codtipotitulo);

    -- de -> para (codigos novos), texto na frente da observacao, portador
    CREATE TEMP TABLE tl2_de_para (
        de bigint PRIMARY KEY, para bigint NOT NULL, prefixo varchar(30), codportador bigint
    ) ON COMMIT DROP;
    INSERT INTO tl2_de_para VALUES
        (936, 200, 'PROVISAO', NULL),
        (933, 200, NULL, 202007),            -- Pacote Soja: barter
        (934, 200, NULL, 202007),            -- Pacote Milho
        (942, 200, NULL, 202007),            -- Pacote Milheto
        (944, 200, NULL, 202007),            -- Troca Prod. Agricola
        (932, 200, NULL, v_permuta),         -- Permuta
        (929, 200, NULL, NULL),              -- DDA a Pagar
        (943, 200, 'CONSIGNACAO', NULL),
        (941, 200, 'RETORNO CONSERTO', NULL),
        (5,   200, 'OUTRAS ENTRADAS', NULL),
        (130, 200, 'CREDITO FORNECEDOR', NULL),
        (949, 200, 'EMPRESTIMO', NULL),
        (6,   200, 'COMODATO', NULL),
        (947, 122, 'DEVOLUCAO CONSIGNACAO', NULL),
        (8,   122, 'REMESSA CONSERTO', NULL),
        (948, 122, 'GARANTIA', NULL),
        (940, 100, 'DESCONTO CONVENIO', NULL),
        (939, 100, 'ALUGUEL', NULL);

    SELECT count(*) INTO v_dif
      FROM tl2_de_para dp
      JOIN tbltipotitulo a ON a.codtipotitulo = dp.de
      JOIN tbltipotitulo b ON b.codtipotitulo = dp.para
     WHERE a.natureza <> b.natureza
       AND EXISTS (SELECT 1 FROM tbltitulo t WHERE t.codtipotitulo = dp.de);
    IF v_dif > 0 THEN
        RAISE EXCEPTION 'Inativos: % pares de natureza diferente', v_dif;
    END IF;

    UPDATE tbltitulo t
       SET codtipotitulo = dp.para,
           observacao = CASE
               WHEN dp.prefixo IS NULL THEN t.observacao
               WHEN coalesce(t.observacao, '') = '' THEN dp.prefixo
               ELSE left(dp.prefixo || E'\n' || t.observacao, 255)
           END,
           codportador = coalesce(dp.codportador, t.codportador)
      FROM tl2_de_para dp
     WHERE t.codtipotitulo = dp.de;
    GET DIAGNOSTICS v_qtd = ROW_COUNT;
    RAISE NOTICE 'Inativos: titulos transferidos: %', v_qtd;

    UPDATE tblnaturezaoperacao n SET codtipotitulo = dp.para
      FROM tl2_de_para dp WHERE n.codtipotitulo = dp.de;
    GET DIAGNOSTICS v_qtd = ROW_COUNT;
    RAISE NOTICE 'Inativos: naturezas reapontadas: %', v_qtd;

    DELETE FROM tbltipotitulo tt
     USING tl2_de_para dp
     WHERE tt.codtipotitulo = dp.de
       AND NOT EXISTS (SELECT 1 FROM tbltitulo t WHERE t.codtipotitulo = tt.codtipotitulo)
       AND NOT EXISTS (SELECT 1 FROM tblnaturezaoperacao n WHERE n.codtipotitulo = tt.codtipotitulo);
    SELECT count(*) INTO v_dif FROM tbltipotitulo WHERE codtipotitulo IN (SELECT de FROM tl2_de_para);
    IF v_dif > 0 THEN
        RAISE EXCEPTION 'Inativos: % tipos que sumiriam ainda tem referencia', v_dif;
    END IF;

    -- historicos que ficam inativos, renumerados (o 130 ja' esta' livre)
    FOR r IN SELECT * FROM (VALUES
            (922::bigint, 130::bigint, 'Transferência Saída'),
            (926, 131, 'Uso e Consumo'),
            (925, 132, 'Perda'),
            (924, 133, 'Doação, Brinde'),
            (923, 230, 'Transferência Entrada')
        ) v(antigo, novo, nome) LOOP
        UPDATE tbltipotitulo
           SET codtipotitulo = r.novo, tipotitulo = r.nome,
               inativo = coalesce(inativo, now())
         WHERE codtipotitulo = r.antigo;
    END LOOP;

    SELECT count(*) INTO v_dif
      FROM tl2_antes a
      JOIN tbltitulo t USING (codtitulo)
      JOIN tbltipotitulo tt ON tt.codtipotitulo = t.codtipotitulo
     WHERE t.valor IS DISTINCT FROM a.valor
        OR t.saldo IS DISTINCT FROM a.saldo
        OR tt.natureza <> a.natureza;
    IF v_dif > 0 THEN
        RAISE EXCEPTION 'Inativos: % titulos mudaram de valor, saldo ou natureza', v_dif;
    END IF;
    SELECT count(*) INTO v_dif FROM tbltipotitulo
     WHERE codtipotitulo NOT IN (100, 101, 102, 111, 120, 121, 122, 200, 201, 202, 210, 211, 212, 220,
                                 130, 131, 132, 133, 230);
    IF v_dif > 0 THEN
        RAISE EXCEPTION 'Inativos: sobraram % tipos fora da lista final', v_dif;
    END IF;
END $$;

SELECT setval('tbltipotitulo_codtipotitulo_seq', (SELECT max(codtipotitulo) FROM tbltipotitulo));

-- Conferencia: todos os tipos, com quantos titulos e naturezas
SELECT tt.codtipotitulo, tt.tipotitulo, tt.natureza, tt.movimentaportador,
       tt.inativo IS NOT NULL AS inativo,
       (SELECT count(*) FROM tbltitulo t WHERE t.codtipotitulo = tt.codtipotitulo) AS titulos,
       (SELECT count(*) FROM tblnaturezaoperacao n WHERE n.codtipotitulo = tt.codtipotitulo) AS naturezas
  FROM tbltipotitulo tt
 ORDER BY tt.codtipotitulo;

COMMIT;

-- =====================================================================
-- Configuracao do PDV na tabela (TASK-46)
--
-- O "Meu Dispositivo" do negocios guardava local de estoque, natureza de
-- operacao, impressora, maquineta e portador PIX so' no localStorage do
-- navegador. Passam a morar no tblpdv; o PDV recebe na sincronizacao.
--
--   codestoquelocal      — local de estoque padrao dos negocios
--   codnaturezaoperacao  — natureza padrao dos negocios
--   impressora           — nome da impressora (api/printers.json)
--   codmaquineta         — maquineta pre-selecionada no cartao
--   codportadorpix       — portador pre-selecionado no PIX
--                          (o codportador continua sendo a gaveta)
--
-- A 1a sincronizacao de cada PDV depois do deploy preenche as colunas
-- vazias com o que estava no navegador (PdvService::dispositivo).
--
-- Autorizado = ativo: o dispositivo nasce inativo e ativar e' o que o
-- autoriza. Os que esperavam autorizacao (nao autorizados e ativos) viram
-- inativos aqui, antes do codigo novo, que so' olha o inativo. A coluna
-- autorizado sai no pdv_autorizado_drop.sql, depois do deploy.
--
-- Historico de IP e localizacao: tblpdvlocalizacao tem uma linha por
-- periodo no mesmo lugar (criacao = 1a sincronizacao, alteracao = ultima,
-- sincronizacoes = quantas); mudou o IP ou a posicao, linha nova. O atual
-- e' a ultima. A carga inicial copia o que esta hoje no tblpdv, e as
-- colunas ip, latitude, longitude e precisao saem no
-- pdv_autorizado_drop.sql, depois do deploy.
--
--   sincronizacaocompleta — a data que o botao Sincronizar do PDV mostra (a
--                          mais antiga entre os cadastros baixados); o PDV
--                          avisa no fim de cada sincronizacao
--
-- Idempotente (IF NOT EXISTS + guards em pg_constraint). Transacional,
-- menos os indices do fim (CONCURRENTLY nao roda dentro de transacao).
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

-- ADD COLUMN sem DEFAULT nao reescreve a tabela, mas pega ACCESS EXCLUSIVE
-- por milissegundos; com transacao ociosa segurando lock o script desiste
-- em 5s e faz rollback.
SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

ALTER TABLE tblpdv ADD COLUMN IF NOT EXISTS codestoquelocal bigint;
ALTER TABLE tblpdv ADD COLUMN IF NOT EXISTS codnaturezaoperacao bigint;
ALTER TABLE tblpdv ADD COLUMN IF NOT EXISTS impressora varchar(100);
ALTER TABLE tblpdv ADD COLUMN IF NOT EXISTS codmaquineta bigint;
ALTER TABLE tblpdv ADD COLUMN IF NOT EXISTS codportadorpix bigint;
ALTER TABLE tblpdv ADD COLUMN IF NOT EXISTS sincronizacaocompleta timestamp(0) without time zone;

-- esperando autorizacao = inativo (so' enquanto a coluna existe)
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

DO $$
DECLARE
    fk record;
BEGIN
    FOR fk IN
        SELECT * FROM (VALUES
            ('fk_tblpdv_tblestoquelocal', 'tblpdv', 'codestoquelocal', 'tblestoquelocal', 'codestoquelocal'),
            ('fk_tblpdv_tblnaturezaoperacao', 'tblpdv', 'codnaturezaoperacao', 'tblnaturezaoperacao', 'codnaturezaoperacao'),
            ('fk_tblpdv_tblmaquineta', 'tblpdv', 'codmaquineta', 'tblmaquineta', 'codmaquineta'),
            ('fk_tblpdv_tblportador_pix', 'tblpdv', 'codportadorpix', 'tblportador', 'codportador')
        ) AS t(nome, tabela, coluna, ref, refcoluna)
    LOOP
        IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = fk.nome) THEN
            EXECUTE format(
                'ALTER TABLE %I ADD CONSTRAINT %I FOREIGN KEY (%I) REFERENCES %I (%I) ON UPDATE CASCADE',
                fk.tabela, fk.nome, fk.coluna, fk.ref, fk.refcoluna
            );
        END IF;
    END LOOP;
END $$;

CREATE TABLE IF NOT EXISTS tblpdvlocalizacao (
    codpdvlocalizacao bigserial,
    codpdv bigint NOT NULL,
    ip inet,
    latitude double precision,
    longitude double precision,
    precisao double precision,
    sincronizacoes integer NOT NULL DEFAULT 1,
    criacao timestamp(0) without time zone DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) without time zone DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT pk_tblpdvlocalizacao PRIMARY KEY (codpdvlocalizacao),
    CONSTRAINT fk_tblpdvlocalizacao_tblpdv FOREIGN KEY (codpdv)
        REFERENCES tblpdv (codpdv) ON UPDATE CASCADE,
    CONSTRAINT fk_tblpdvlocalizacao_tblusuario FOREIGN KEY (codusuariocriacao)
        REFERENCES tblusuario (codusuario) ON UPDATE CASCADE,
    CONSTRAINT fk_tblpdvlocalizacao_tblusuario_0 FOREIGN KEY (codusuarioalteracao)
        REFERENCES tblusuario (codusuario) ON UPDATE CASCADE
);

-- tabela nova: o indice pode ser criado dentro da transacao
CREATE INDEX IF NOT EXISTS idx_tblpdvlocalizacao_codpdv
    ON tblpdvlocalizacao (codpdv, codpdvlocalizacao);

-- carga inicial: a ultima localizacao de cada PDV vira a 1a linha do
-- historico (so' enquanto as colunas existem e so' para quem ainda nao tem)
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'tblpdv' AND column_name = 'ip'
    ) THEN
        EXECUTE '
            INSERT INTO tblpdvlocalizacao
                (codpdv, ip, latitude, longitude, precisao, criacao, alteracao)
            SELECT
                p.codpdv, p.ip, p.latitude, p.longitude, p.precisao,
                coalesce(p.alteracao, p.criacao, now()),
                coalesce(p.alteracao, p.criacao, now())
            FROM tblpdv p
            WHERE (p.ip IS NOT NULL OR p.latitude IS NOT NULL)
            AND NOT EXISTS (
                SELECT 1 FROM tblpdvlocalizacao l WHERE l.codpdv = p.codpdv
            )
            ORDER BY p.codpdv
        ';
    END IF;
END $$;

COMMIT;

-- Ultimos registros na pagina do dispositivo (os 20 negocios, pagamentos e
-- ocorrencias mais recentes do PDV, por data). So' com o indice em codpdv, o
-- "where codpdv = X order by data desc limit 20" desce o indice da data
-- inteiro filtrando o PDV: o que parou de vender ha tempo varre milhoes de
-- linhas (2 s num PDV parado desde 2024). O indice (codpdv, data) entrega os
-- ultimos direto (< 1 ms). CONCURRENTLY para nao travar as vendas enquanto cria.
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_tblnegocio_codpdv_lancamento
    ON tblnegocio (codpdv, lancamento) WHERE codpdv IS NOT NULL;
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_tblpagamento_codpdv_transacao
    ON tblpagamento (codpdv, transacao) WHERE codpdv IS NOT NULL;
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_tblocorrencia_codpdv_criacao
    ON tblocorrencia (codpdv, criacao) WHERE codpdv IS NOT NULL;

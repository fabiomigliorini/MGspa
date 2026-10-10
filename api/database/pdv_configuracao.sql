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

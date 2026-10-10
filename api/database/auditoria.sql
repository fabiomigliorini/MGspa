-- =====================================================================
-- Auditoria (TASK-204): um lugar so' para toda alteracao relevante.
--
--   tblauditoria  — o que mudou (so' os campos relevantes que mudaram),
--                   em qual registro (tabela + codigo, FK generica), o
--                   tipo, a justificativa e quem/quando. Ninguem confere:
--                   o livro de ocorrencias (TASK-205) amarra as que o
--                   gerente precisa ver (tblocorrenciaauditoria, N:N).
--
-- No lugar de:
--   tblpagamentocorrecao          — trilha da conferencia (conferencia.sql,
--                                   nunca rodou em producao)
--   tblportadormovimentocorrecao  — trilha da data alterada (TASK-204,
--                                   nunca rodou em producao)
--
-- E sai a replicacao entre bases de 2011, que nenhum codigo usa: as
-- tabelas tblauditoria (o nome volta aqui, com outro desenho),
-- tblauditoriatransmissao, tblauditoriaexcecao e tblbaseremota, as funcoes
-- geraauditoria() e criatriggers_geraauditoria(boolean) e o usuario de
-- banco mgsis_replicacao (superusuario com login). O mgsis_yii fica: o
-- MGsis conecta com ele.
--
-- Idempotente (IF EXISTS / IF NOT EXISTS + guards). Transacional: se o
-- DROP ROLE esbarrar em dependencia em outra base, nada fica pela metade.
-- Roda depois do conferencia.sql e antes do ocorrencia.sql.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

-- ---------------------------------------------------------------------
-- 1. Replicacao entre bases (2011)
-- ---------------------------------------------------------------------
DROP FUNCTION IF EXISTS criatriggers_geraauditoria(boolean);
DROP FUNCTION IF EXISTS geraauditoria();
DROP TABLE IF EXISTS tblauditoriatransmissao;
DROP TABLE IF EXISTS tblauditoriaexcecao;
DROP TABLE IF EXISTS tblbaseremota;
-- so' a antiga tem operacao (a nova, da secao 3, nao): rodar de novo nao
-- apaga a nova
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'tblauditoria' AND column_name = 'operacao'
    ) THEN
        DROP TABLE tblauditoria;
    END IF;
END $$;

-- ---------------------------------------------------------------------
-- 2. Trilhas que a tblauditoria substitui
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS tblpagamentocorrecao;
DROP TABLE IF EXISTS tblportadormovimentocorrecao;

-- ---------------------------------------------------------------------
-- 3. Auditoria
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tblauditoria (
    codauditoria bigserial NOT NULL,
    -- registro alterado (FK generica)
    tabela varchar(50) NOT NULL,
    codigo bigint NOT NULL,
    -- 1 data alterada, 2 data do cancelamento alterada, 3 corrigido na
    -- conferencia, 4 registro indevido, 5 incluido na conferencia;
    -- PDV monitorado (TASK-205): 6 item excluido, 7 quantidade alterada,
    -- 8 preco diferente do cadastro, 9 vale compras excluido, 10 pagamento
    -- apagado, 11 parcela apagada, 12 negocio cancelado, 13 estornado
    -- (constantes em Mg\Auditoria\AuditoriaService)
    tipo smallint NOT NULL,
    -- so' os campos relevantes que mudaram; antes nulo no incluido
    antes jsonb,
    depois jsonb,
    -- obrigatoria quando uma pessoa corrige pela tela (regra no Service);
    -- vazia no que o servidor detecta sozinho
    justificativa varchar(300),
    criacao timestamp(0) without time zone DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) without time zone DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT pk_tblauditoria PRIMARY KEY (codauditoria)
);

CREATE INDEX IF NOT EXISTS idx_tblauditoria_tabela_codigo
    ON tblauditoria (tabela, codigo);

DO $$
DECLARE
    fk record;
BEGIN
    FOR fk IN
        SELECT * FROM (VALUES
            ('fk_tblauditoria_tblusuario', 'tblauditoria', 'codusuariocriacao', 'tblusuario', 'codusuario'),
            ('fk_tblauditoria_tblusuario_0', 'tblauditoria', 'codusuarioalteracao', 'tblusuario', 'codusuario')
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

-- ---------------------------------------------------------------------
-- 4. Usuario de banco da replicacao
-- ---------------------------------------------------------------------
-- Dono da tblcobrancahistoricotitulo (viva): passa para o mgsis antes
-- de sair.
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'mgsis_replicacao') THEN
        REASSIGN OWNED BY mgsis_replicacao TO mgsis;
        DROP OWNED BY mgsis_replicacao;
        DROP ROLE mgsis_replicacao;
    END IF;
END $$;

COMMIT;

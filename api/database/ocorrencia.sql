-- =====================================================================
-- Livro de ocorrencias do PDV (TASK-205).
--
-- SOMENTE ESTRUTURA (DDL). Nenhum dado historico e' tocado: cada PDV so'
-- passa a gerar ocorrencia quando ganha a data de monitoramento.
--
--   tblocorrencia  — o que o gerente confere, registrado pelo servidor:
--                    item ou vale excluido, quantidade diminuida, preco fora
--                    do cadastro, pagamento ou parcela apagados, saida sem
--                    financeiro, negocio cancelado, pagamento/vale estornado,
--                    desconto acima do permitido, negocio esquecido e as
--                    correcoes de lancamento da TASK-204
--   tblocorrenciaauditoria — N:N com a tblauditoria, onde fica o fato
--                    (antes/depois, justificativa); a ocorrencia so' agrupa
--   tblpdv         — monitoramento (a partir de quando o PDV e' monitorado;
--                    nulo = nao monitora) e minutosesquecido (tempo parado
--                    ate o negocio aberto virar ocorrencia)
--
-- Idempotente (IF NOT EXISTS + guards em pg_constraint). Transacional.
-- Roda depois do auditoria.sql (FK para a tblauditoria) e antes do
-- tipo_titulo_limpeza.sql (o ultimo do go-live).
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

-- ADD COLUMN em tblpdv com DEFAULT constante nao reescreve a tabela, mas
-- pega ACCESS EXCLUSIVE por milissegundos; com transacao ociosa segurando
-- lock o script desiste em 5s e faz rollback. Nunca remover estes dois SET.
SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

-- ---------------------------------------------------------------------
-- 1. Monitoramento do PDV
-- ---------------------------------------------------------------------
ALTER TABLE tblpdv ADD COLUMN IF NOT EXISTS monitoramento date;
ALTER TABLE tblpdv ADD COLUMN IF NOT EXISTS minutosesquecido integer NOT NULL DEFAULT 120;

-- ---------------------------------------------------------------------
-- 2. Livro de ocorrencias
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tblocorrencia (
    codocorrencia bigserial NOT NULL,
    -- 1 item excluido, 2 quantidade diminuida, 3 preco abaixo do cadastro,
    -- 4 preco acima do cadastro, 5 vale compras excluido, 6 pagamento
    -- apagado, 7 parcela a prazo apagada, 8 saida sem financeiro,
    -- 10 negocio cancelado, 11 pagamento estornado, 12 vale estornado,
    -- 13 desconto acima do permitido, 14 negocio esquecido, 15 data
    -- alterada, 16 data do cancelamento alterada, 17 corrigido na
    -- conferencia, 18 registro indevido, 19 incluido na conferencia
    tipo smallint NOT NULL,
    -- registro afetado (FK generica): os tipos 1 a 8 apontam para o
    -- negocio (uma por negocio e tipo); 15 a 19 para a tblauditoria
    tabela varchar(50) NOT NULL,
    codigo bigint,
    codnegocio bigint,
    codpdv bigint,
    codfilial bigint NOT NULL,
    -- quem operou (no esquecido, criado pelo agendador,
    -- codusuariocriacao nao e' o operador)
    codusuario bigint,
    descricao varchar(300) NOT NULL,
    -- quanto a venda perdeu, em R$
    valor numeric(14,2) NOT NULL DEFAULT 0,
    conferencia timestamp(0) without time zone,
    codusuarioconferencia bigint,
    observacao varchar(500),
    criacao timestamp(0) without time zone DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) without time zone DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT pk_tblocorrencia PRIMARY KEY (codocorrencia)
);

-- uma vez so' por registro e tipo
CREATE UNIQUE INDEX IF NOT EXISTS uk_tblocorrencia
    ON tblocorrencia (tipo, tabela, codigo);
CREATE INDEX IF NOT EXISTS idx_tblocorrencia_codnegocio
    ON tblocorrencia (codnegocio) WHERE codnegocio IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_tblocorrencia_codfilial_criacao
    ON tblocorrencia (codfilial, criacao);
CREATE INDEX IF NOT EXISTS idx_tblocorrencia_pendente
    ON tblocorrencia (codfilial, criacao) WHERE conferencia IS NULL;

-- ---------------------------------------------------------------------
-- 3. Ocorrencia x auditoria (N:N)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tblocorrenciaauditoria (
    codocorrenciaauditoria bigserial NOT NULL,
    codocorrencia bigint NOT NULL,
    codauditoria bigint NOT NULL,
    criacao timestamp(0) without time zone DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) without time zone DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT pk_tblocorrenciaauditoria PRIMARY KEY (codocorrenciaauditoria)
);

CREATE UNIQUE INDEX IF NOT EXISTS uk_tblocorrenciaauditoria
    ON tblocorrenciaauditoria (codocorrencia, codauditoria);
CREATE INDEX IF NOT EXISTS idx_tblocorrenciaauditoria_codauditoria
    ON tblocorrenciaauditoria (codauditoria);

-- ---------------------------------------------------------------------
-- 4. Chaves estrangeiras
-- ---------------------------------------------------------------------
DO $$
DECLARE
    fk record;
BEGIN
    FOR fk IN
        SELECT * FROM (VALUES
            ('fk_tblocorrencia_tblnegocio', 'tblocorrencia', 'codnegocio', 'tblnegocio', 'codnegocio'),
            ('fk_tblocorrencia_tblpdv', 'tblocorrencia', 'codpdv', 'tblpdv', 'codpdv'),
            ('fk_tblocorrencia_tblfilial', 'tblocorrencia', 'codfilial', 'tblfilial', 'codfilial'),
            ('fk_tblocorrencia_tblusuario_operador', 'tblocorrencia', 'codusuario', 'tblusuario', 'codusuario'),
            ('fk_tblocorrencia_tblusuario_conferencia', 'tblocorrencia', 'codusuarioconferencia', 'tblusuario', 'codusuario'),
            ('fk_tblocorrencia_tblusuario', 'tblocorrencia', 'codusuariocriacao', 'tblusuario', 'codusuario'),
            ('fk_tblocorrencia_tblusuario_0', 'tblocorrencia', 'codusuarioalteracao', 'tblusuario', 'codusuario'),
            ('fk_tblocorrenciaauditoria_tblocorrencia', 'tblocorrenciaauditoria', 'codocorrencia', 'tblocorrencia', 'codocorrencia'),
            ('fk_tblocorrenciaauditoria_tblauditoria', 'tblocorrenciaauditoria', 'codauditoria', 'tblauditoria', 'codauditoria'),
            ('fk_tblocorrenciaauditoria_tblusuario', 'tblocorrenciaauditoria', 'codusuariocriacao', 'tblusuario', 'codusuario'),
            ('fk_tblocorrenciaauditoria_tblusuario_0', 'tblocorrenciaauditoria', 'codusuarioalteracao', 'tblusuario', 'codusuario')
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

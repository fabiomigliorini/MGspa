-- =====================================================================
-- Requisicao entre lojas (TASK-55, etapa 3 do doc-6).
--
--   tblrequisicao     — quem pede (requisitante), quem atende (requisitada;
--                       nula = departamento de compras, so' estrutura), o
--                       destino da mercadoria, o cliente, o codigo de retirada
--                       (so' o lado de quem pediu ve) e o status, gravado pelo
--                       processo idempotente da requisicao (nunca calculado na
--                       consulta).
--   tblrequisicaoitem — o que foi pedido (barra + quantidade, a mesma variacao
--                       pode repetir em outra embalagem), o atendido (gravado
--                       pelo processo, na unidade base) e a justificativa de
--                       quem atendeu (motivo + substituto + observacao).
--
-- SOMENTE ESTRUTURA (DDL). Idempotente (IF NOT EXISTS + guards em
-- pg_constraint). Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

CREATE TABLE IF NOT EXISTS tblrequisicao (
    codrequisicao bigserial NOT NULL,
    -- loja que pede (e para onde vai a transferencia)
    codestoquelocalrequisitante bigint NOT NULL,
    -- loja que atende; nula so' quando quem atende e' o departamento de compras
    codestoquelocalrequisitada bigint,
    -- L loja, C compras (preparado, sem tela)
    atendimento char(1) NOT NULL DEFAULT 'L',
    -- R cliente retira na requisitada, E entregar no endereco, L vai para a requisitante
    destino char(1) NOT NULL,
    codpessoa bigint,
    -- quem retira ou recebe: um telefone para cada nome, na mesma posicao; so' digitos,
    -- com o DDD: 10 (fixo) ou 11 (celular)
    nomes varchar(100)[],
    telefones varchar(11)[],
    endereco varchar(300),
    -- 6 digitos; so' a requisitante ve
    codigoretirada char(6) NOT NULL,
    -- negocio de venda de onde a requisicao nasceu
    codnegocio bigint,
    -- dispositivo que registrou (a via da requisitante sai na impressora dele)
    codpdv bigint NOT NULL,
    observacoes varchar(500),
    -- A aguardando, S em separacao, P separado parcial / pendente, T separado
    -- total, D separado com divergencias, R com o entregador (destino E,
    -- requisicao_entregador.sql), E entregue, C cancelada
    status char(1) NOT NULL DEFAULT 'A',
    -- finalizacao manual (com divergencias), por quem atende
    finalizacao timestamp(0) without time zone,
    codusuariofinalizacao bigint,
    cancelamento timestamp(0) without time zone,
    codusuariocancelamento bigint,
    criacao timestamp(0) without time zone DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) without time zone DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT pk_tblrequisicao PRIMARY KEY (codrequisicao),
    CONSTRAINT tblrequisicao_atendimento_check CHECK (atendimento IN ('L', 'C')),
    CONSTRAINT tblrequisicao_requisitada_check
        CHECK ((atendimento = 'L') = (codestoquelocalrequisitada IS NOT NULL)),
    CONSTRAINT tblrequisicao_lojas_check
        CHECK (codestoquelocalrequisitada IS DISTINCT FROM codestoquelocalrequisitante),
    CONSTRAINT tblrequisicao_destino_check CHECK (destino IN ('R', 'E', 'L')),
    CONSTRAINT tblrequisicao_nomes_check
        CHECK (destino = 'L' OR coalesce(cardinality(nomes), 0) > 0),
    CONSTRAINT tblrequisicao_telefones_check
        CHECK (coalesce(cardinality(telefones), 0) = coalesce(cardinality(nomes), 0)),
    CONSTRAINT tblrequisicao_endereco_check CHECK (destino <> 'E' OR endereco IS NOT NULL),
    CONSTRAINT tblrequisicao_status_check CHECK (status IN ('A', 'S', 'P', 'T', 'D', 'R', 'E', 'C')),
    CONSTRAINT tblrequisicao_codigoretirada_check CHECK (codigoretirada ~ '^[0-9]{6}$')
);

-- A versao de 21/10 deste arquivo criava nome/telefone (um so'); o CREATE TABLE IF NOT
-- EXISTS acima nao mexe na tabela que ja' existe. Converte para nomes/telefones.
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = current_schema()
        AND table_name = 'tblrequisicao'
        AND column_name = 'nome'
    ) THEN
        ALTER TABLE tblrequisicao ADD COLUMN IF NOT EXISTS nomes varchar(100)[];
        ALTER TABLE tblrequisicao ADD COLUMN IF NOT EXISTS telefones varchar(11)[];
        UPDATE tblrequisicao SET
            nomes = ARRAY[nome],
            telefones = ARRAY[NULLIF(left(regexp_replace(coalesce(telefone, ''), '\D', '', 'g'), 11), '')]
        WHERE nome IS NOT NULL
        AND nomes IS NULL;
        ALTER TABLE tblrequisicao DROP CONSTRAINT IF EXISTS tblrequisicao_nome_check;
        ALTER TABLE tblrequisicao DROP COLUMN nome;
        ALTER TABLE tblrequisicao DROP COLUMN IF EXISTS telefone;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'tblrequisicao_nomes_check') THEN
        ALTER TABLE tblrequisicao ADD CONSTRAINT tblrequisicao_nomes_check
            CHECK (destino = 'L' OR coalesce(cardinality(nomes), 0) > 0);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'tblrequisicao_telefones_check') THEN
        ALTER TABLE tblrequisicao ADD CONSTRAINT tblrequisicao_telefones_check
            CHECK (coalesce(cardinality(telefones), 0) = coalesce(cardinality(nomes), 0));
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_tblrequisicao_requisitada_status
    ON tblrequisicao (codestoquelocalrequisitada, status);
CREATE INDEX IF NOT EXISTS idx_tblrequisicao_requisitante_status
    ON tblrequisicao (codestoquelocalrequisitante, status);
CREATE INDEX IF NOT EXISTS idx_tblrequisicao_codnegocio
    ON tblrequisicao (codnegocio) WHERE codnegocio IS NOT NULL;

CREATE TABLE IF NOT EXISTS tblrequisicaoitem (
    codrequisicaoitem bigserial NOT NULL,
    codrequisicao bigint NOT NULL,
    codprodutobarra bigint NOT NULL,
    -- pedido, na embalagem da barra; 0 = incluido por quem atende ("enviado a mais")
    quantidade numeric(14,3) NOT NULL,
    -- atendido, na unidade base da variacao (gravado pelo processo)
    quantidadeatendida numeric(14,3) NOT NULL DEFAULT 0,
    -- E estoque do sistema errado, A avariado/vencido, N nao localizado,
    -- M enviado a mais, S sem saldo
    motivo char(1),
    -- substituido por (complementar ao motivo)
    codprodutobarrasubstituto bigint,
    observacao varchar(500),
    criacao timestamp(0) without time zone DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) without time zone DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT pk_tblrequisicaoitem PRIMARY KEY (codrequisicaoitem),
    CONSTRAINT tblrequisicaoitem_quantidade_check CHECK (quantidade >= 0),
    CONSTRAINT tblrequisicaoitem_motivo_check CHECK (motivo IN ('E', 'A', 'N', 'M', 'S'))
);

CREATE UNIQUE INDEX IF NOT EXISTS uk_tblrequisicaoitem
    ON tblrequisicaoitem (codrequisicao, codprodutobarra);

DO $$
DECLARE
    fk record;
BEGIN
    FOR fk IN
        SELECT * FROM (VALUES
            ('fk_tblrequisicao_tblestoquelocal_requisitante', 'tblrequisicao', 'codestoquelocalrequisitante', 'tblestoquelocal', 'codestoquelocal', ''),
            ('fk_tblrequisicao_tblestoquelocal_requisitada', 'tblrequisicao', 'codestoquelocalrequisitada', 'tblestoquelocal', 'codestoquelocal', ''),
            ('fk_tblrequisicao_tblpessoa', 'tblrequisicao', 'codpessoa', 'tblpessoa', 'codpessoa', ''),
            ('fk_tblrequisicao_tblnegocio', 'tblrequisicao', 'codnegocio', 'tblnegocio', 'codnegocio', ''),
            ('fk_tblrequisicao_tblpdv', 'tblrequisicao', 'codpdv', 'tblpdv', 'codpdv', ''),
            ('fk_tblrequisicao_tblusuario_finalizacao', 'tblrequisicao', 'codusuariofinalizacao', 'tblusuario', 'codusuario', ''),
            ('fk_tblrequisicao_tblusuario_cancelamento', 'tblrequisicao', 'codusuariocancelamento', 'tblusuario', 'codusuario', ''),
            ('fk_tblrequisicao_tblusuario', 'tblrequisicao', 'codusuariocriacao', 'tblusuario', 'codusuario', ''),
            ('fk_tblrequisicao_tblusuario_0', 'tblrequisicao', 'codusuarioalteracao', 'tblusuario', 'codusuario', ''),
            ('fk_tblrequisicaoitem_tblrequisicao', 'tblrequisicaoitem', 'codrequisicao', 'tblrequisicao', 'codrequisicao', ' ON DELETE CASCADE'),
            ('fk_tblrequisicaoitem_tblprodutobarra', 'tblrequisicaoitem', 'codprodutobarra', 'tblprodutobarra', 'codprodutobarra', ''),
            ('fk_tblrequisicaoitem_tblprodutobarra_substituto', 'tblrequisicaoitem', 'codprodutobarrasubstituto', 'tblprodutobarra', 'codprodutobarra', ''),
            ('fk_tblrequisicaoitem_tblusuario', 'tblrequisicaoitem', 'codusuariocriacao', 'tblusuario', 'codusuario', ''),
            ('fk_tblrequisicaoitem_tblusuario_0', 'tblrequisicaoitem', 'codusuarioalteracao', 'tblusuario', 'codusuario', '')
        ) AS t(nome, tabela, coluna, ref, refcoluna, extra)
    LOOP
        IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = fk.nome) THEN
            EXECUTE format(
                'ALTER TABLE %I ADD CONSTRAINT %I FOREIGN KEY (%I) REFERENCES %I (%I) ON UPDATE CASCADE%s',
                fk.tabela, fk.nome, fk.coluna, fk.ref, fk.refcoluna, fk.extra
            );
        END IF;
    END LOOP;
END $$;

COMMIT;

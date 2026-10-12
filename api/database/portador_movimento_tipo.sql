-- =====================================================================
-- Redefinicao do dinheiro (doc-4, "Redefinicao do dominio do dinheiro";
-- TASK-39 R7).
--
--   tblportadormovimento   — registro principal do saldo. Ganha o tipo:
--                            P pagamento (codpagamento, gerado pelo
--                            PortadorMovimentoService::sincronizar), A ajuste
--                            (so' a linha) e T transferencia (duas linhas
--                            ligadas pelo par). Ajuste e transferencia tem
--                            estado (P a confirmar, E feito, C cancelado),
--                            observacao, confirmacao e cancelamento; o
--                            backend mantem as duas linhas da transferencia
--                            iguais. `inativo` continua so' para a troca
--                            interna das linhas do pagamento.
--   tblportadorusuario     — quem pode o que em cada portador (papel D
--                            depositante, O operador, G gestor); substitui a
--                            regra por grupo e filial. Nasce da regra de hoje.
--   tblportador.tolerancia — diferenca de contagem que fecha o periodo
--   tblportadorperiodo.diferenca — contagem final - saldo final
--
-- Migra os ajustes (tblpagamento.motivo A) e as transferencias (pagamento com
-- origem e destino) para linhas do movimento e apaga esses pagamentos.
--
-- Idempotente. Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

-- ---- portador e periodo ----

ALTER TABLE tblportador ADD COLUMN IF NOT EXISTS tolerancia numeric(14,2) NOT NULL DEFAULT 2;
ALTER TABLE tblportadorperiodo ADD COLUMN IF NOT EXISTS diferenca numeric(14,2);

-- ---- movimento ----

ALTER TABLE tblportadormovimento
    ADD COLUMN IF NOT EXISTS tipo character(1) NOT NULL DEFAULT 'P',
    ADD COLUMN IF NOT EXISTS estado character(1),
    ADD COLUMN IF NOT EXISTS observacoes character varying(300),
    ADD COLUMN IF NOT EXISTS codportadormovimentopar bigint,
    ADD COLUMN IF NOT EXISTS confirmacao timestamp(0) without time zone,
    ADD COLUMN IF NOT EXISTS codusuarioconfirmacao bigint,
    ADD COLUMN IF NOT EXISTS cancelamento timestamp(0) without time zone,
    ADD COLUMN IF NOT EXISTS codusuariocancelamento bigint,
    ADD COLUMN IF NOT EXISTS justificativa character varying(300);

ALTER TABLE tblportadormovimento ALTER COLUMN codpagamento DROP NOT NULL;

-- ajustes: a linha do pagamento vira a linha do ajuste
UPDATE tblportadormovimento m SET
    tipo = 'A',
    codpagamento = NULL,
    estado = p.estado,
    observacoes = p.observacoes,
    inativo = NULL,
    cancelamento = p.cancelamento,
    codusuariocancelamento = p.codusuariocancelamento,
    justificativa = p.justificativa,
    criacao = p.criacao,
    codusuariocriacao = p.codusuariocriacao
FROM tblpagamento p
WHERE p.codpagamento = m.codpagamento
AND p.motivo = 'A';

-- transferencias: as duas linhas do pagamento viram o par
CREATE TEMP TABLE tmp_transferencia ON COMMIT DROP AS
SELECT p.codpagamento,
    (SELECT m.codportadormovimento FROM tblportadormovimento m
     WHERE m.codpagamento = p.codpagamento AND m.codportador = p.codportadororigem
     ORDER BY m.codportadormovimento DESC LIMIT 1) AS saida,
    (SELECT m.codportadormovimento FROM tblportadormovimento m
     WHERE m.codpagamento = p.codpagamento AND m.codportador = p.codportadordestino
     ORDER BY m.codportadormovimento DESC LIMIT 1) AS entrada
FROM tblpagamento p
WHERE p.codportadororigem IS NOT NULL
AND p.codportadordestino IS NOT NULL
AND p.codnegocio IS NULL
AND NOT EXISTS (SELECT 1 FROM tblmovimentotitulo mt WHERE mt.codpagamento = p.codpagamento);

-- linhas sobrando (trocas internas antigas) saem
DELETE FROM tblportadormovimento m
USING tmp_transferencia t
WHERE m.codpagamento = t.codpagamento
AND m.codportadormovimento NOT IN (t.saida, t.entrada);

UPDATE tblportadormovimento m SET
    tipo = 'T',
    codpagamento = NULL,
    codportadormovimentopar = CASE WHEN m.codportadormovimento = t.saida THEN t.entrada ELSE t.saida END,
    estado = p.estado,
    observacoes = p.observacoes,
    inativo = NULL,
    confirmacao = CASE WHEN p.estado = 'E' THEN p.efetivacao END,
    codusuarioconfirmacao = CASE WHEN p.estado = 'E' THEN p.codusuarioefetivacao END,
    cancelamento = p.cancelamento,
    codusuariocancelamento = p.codusuariocancelamento,
    justificativa = p.justificativa,
    criacao = p.criacao,
    codusuariocriacao = p.codusuariocriacao
FROM tmp_transferencia t
JOIN tblpagamento p ON p.codpagamento = t.codpagamento
WHERE m.codportadormovimento IN (t.saida, t.entrada);

-- os pagamentos que viraram movimento
DELETE FROM tblpagamento p
WHERE p.motivo = 'A'
OR p.codpagamento IN (SELECT codpagamento FROM tmp_transferencia);

ALTER TABLE tblpagamento DROP CONSTRAINT IF EXISTS tblpagamento_motivo_check;
ALTER TABLE tblpagamento ADD CONSTRAINT tblpagamento_motivo_check
    CHECK (motivo IS NULL OR motivo = ANY (ARRAY['T'::bpchar, 'F'::bpchar, 'R'::bpchar]));

ALTER TABLE tblportadormovimento DROP CONSTRAINT IF EXISTS tblportadormovimento_tipo_check;
ALTER TABLE tblportadormovimento ADD CONSTRAINT tblportadormovimento_tipo_check CHECK (
    tipo = ANY (ARRAY['P'::bpchar, 'A'::bpchar, 'T'::bpchar])
    AND (tipo = 'P') = (codpagamento IS NOT NULL)
    AND (tipo = 'P') = (estado IS NULL)
    AND (estado IS NULL OR estado = ANY (ARRAY['P'::bpchar, 'E'::bpchar, 'C'::bpchar]))
    AND (tipo = 'P' OR inativo IS NULL)
);

ALTER TABLE tblportadormovimento DROP CONSTRAINT IF EXISTS fk_tblportadormovimento_par;
ALTER TABLE tblportadormovimento ADD CONSTRAINT fk_tblportadormovimento_par
    FOREIGN KEY (codportadormovimentopar) REFERENCES tblportadormovimento(codportadormovimento) ON UPDATE CASCADE;
ALTER TABLE tblportadormovimento DROP CONSTRAINT IF EXISTS fk_tblportadormovimento_usuarioconfirmacao;
ALTER TABLE tblportadormovimento ADD CONSTRAINT fk_tblportadormovimento_usuarioconfirmacao
    FOREIGN KEY (codusuarioconfirmacao) REFERENCES tblusuario(codusuario) ON UPDATE CASCADE;
ALTER TABLE tblportadormovimento DROP CONSTRAINT IF EXISTS fk_tblportadormovimento_usuariocancelamento;
ALTER TABLE tblportadormovimento ADD CONSTRAINT fk_tblportadormovimento_usuariocancelamento
    FOREIGN KEY (codusuariocancelamento) REFERENCES tblusuario(codusuario) ON UPDATE CASCADE;

-- as transferencias a confirmar de cada periodo (fechar recusa)
CREATE INDEX IF NOT EXISTS idx_tblportadormovimento_pendente
    ON tblportadormovimento (codportadorperiodo) WHERE estado = 'P';

-- ---- quem pode o que em cada portador ----

CREATE TABLE IF NOT EXISTS tblportadorusuario (
    codportadorusuario bigserial NOT NULL,
    codportador bigint NOT NULL,
    codusuario bigint NOT NULL,
    papel character(1) NOT NULL,
    criacao timestamp(0) without time zone DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) without time zone DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT pk_tblportadorusuario PRIMARY KEY (codportadorusuario),
    CONSTRAINT uk_tblportadorusuario UNIQUE (codportador, codusuario),
    CONSTRAINT tblportadorusuario_papel_check CHECK (papel = ANY (ARRAY['D'::bpchar, 'O'::bpchar, 'G'::bpchar])),
    CONSTRAINT fk_tblportadorusuario_tblportador FOREIGN KEY (codportador) REFERENCES tblportador(codportador) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_tblportadorusuario_tblusuario FOREIGN KEY (codusuario) REFERENCES tblusuario(codusuario) ON UPDATE CASCADE,
    CONSTRAINT fk_tblportadorusuario_usuariocriacao FOREIGN KEY (codusuariocriacao) REFERENCES tblusuario(codusuario) ON UPDATE CASCADE,
    CONSTRAINT fk_tblportadorusuario_usuarioalteracao FOREIGN KEY (codusuarioalteracao) REFERENCES tblusuario(codusuario) ON UPDATE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_tblportadorusuario_codusuario ON tblportadorusuario (codusuario);

-- a lista nasce da regra de hoje (so' na primeira vez): Gerente da filial =
-- gestor na gaveta, cofre e troco; Caixa da filial = operador na gaveta e
-- depositante no cofre e troco; Financeiro = gestor no Caixa Financeiro e nos
-- demais tipos; Gerente = depositante no Caixa Financeiro e nos bancos
-- (deposito). Administrador e' gestor em todos sem estar na lista.
INSERT INTO tblportadorusuario (codportador, codusuario, papel)
SELECT codportador, codusuario, (ARRAY['D', 'O', 'G'])[max(nivel)]
FROM (
    SELECT po.codportador, guu.codusuario,
        CASE
            WHEN po.tipo = 'E' AND po.codportador <> 100 AND gu.grupousuario = 'Gerente' AND guu.codfilial = po.codfilial THEN 3
            WHEN po.tipo = 'E' AND po.codportador <> 100 AND gu.grupousuario = 'Caixa' AND guu.codfilial = po.codfilial
                THEN CASE WHEN EXISTS (SELECT 1 FROM tblpdv d WHERE d.codportador = po.codportador) THEN 2 ELSE 1 END
            WHEN (po.tipo <> 'E' OR po.codportador = 100) AND gu.grupousuario = 'Financeiro' THEN 3
            WHEN (po.tipo = 'B' OR po.codportador = 100) AND gu.grupousuario = 'Gerente' THEN 1
        END AS nivel
    FROM tblportador po
    CROSS JOIN tblgrupousuariousuario guu
    JOIN tblgrupousuario gu ON gu.codgrupousuario = guu.codgrupousuario
    JOIN tblusuario u ON u.codusuario = guu.codusuario AND u.inativo IS NULL
    WHERE gu.grupousuario IN ('Gerente', 'Caixa', 'Financeiro')
    AND NOT EXISTS (SELECT 1 FROM tblportadorusuario x WHERE x.codportador = po.codportador)
) r
WHERE nivel IS NOT NULL
GROUP BY codportador, codusuario;

COMMIT;

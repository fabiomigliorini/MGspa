-- =====================================================================
-- NFS-e Nacional (TASK-279): nota fiscal de servico emitida pelo app notas
-- no Sistema Nacional da NFS-e (nfse.gov.br).
--
--   tbltributacaonacional  codigo de tributacao nacional (6 digitos: item,
--                          subitem e desdobro da LC 116) — Anexo B.
--   tblnbs                 Nomenclatura Brasileira de Servicos (9 digitos),
--                          o "NCM dos servicos" — Anexo B.
--   tblnbsitem             NBS validos para cada item da LC 116 — Anexo VIII
--                          (a nota com NBS fora da correlacao pode ser recusada).
--   tblnotafiscalservico   a nota de servico: uma DPS (serie + numero nossos)
--                          que, autorizada, vira a NFS-e (chave + numero do
--                          Sistema Nacional). Um servico e um valor por nota.
--   tbldistribuicaonfse    caixa de DF-e do ADN: o que o Sistema Nacional
--                          distribuiu para a filial, uma linha por NSU.
--   tblfilial              emitenfse, nfseserie e inscricaomunicipal (Sinop
--                          exige a IM na DPS: rejeicao E0116).
--
-- SOMENTE ESTRUTURA. Os dados das tabelas oficiais estao no nfse_tabelas.sql,
-- que roda DEPOIS deste. Este script ja liga a filial 201 (FDF) com serie 1 e a
-- inscricao municipal dela.
--
-- Idempotente (IF NOT EXISTS + guards em pg_constraint). Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

-- ---------------------------------------------------------------------
-- Filial
-- ---------------------------------------------------------------------
ALTER TABLE tblfilial ADD COLUMN IF NOT EXISTS emitenfse boolean NOT NULL DEFAULT false;
ALTER TABLE tblfilial ADD COLUMN IF NOT EXISTS nfseserie integer;
-- inscricao municipal do prestador: Sinop exige na DPS (rejeicao E0116)
ALTER TABLE tblfilial ADD COLUMN IF NOT EXISTS inscricaomunicipal varchar(15);

-- FDF: serie 1 e a inscricao municipal que Sinop cadastrou no CNC (lida das NFS-e
-- que ela ja emitiu, no ADN de producao)
UPDATE tblfilial SET emitenfse = true, nfseserie = coalesce(nfseserie, 1),
    inscricaomunicipal = coalesce(inscricaomunicipal, '14644')
WHERE codfilial = 201 AND emitenfse = false;

-- ---------------------------------------------------------------------
-- Tabelas oficiais
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbltributacaonacional (
    codtributacaonacional bigserial NOT NULL,
    -- 6 digitos: item (2) + subitem (2) + desdobro nacional (2). Ex.: 170201
    codigo varchar(6) NOT NULL,
    -- item da LC 116 no formato 17.02 (liga com tblnbsitem)
    item varchar(5) NOT NULL,
    descricao text NOT NULL,
    inativo timestamp(0) without time zone,
    criacao timestamp(0) without time zone DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) without time zone DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT pk_tbltributacaonacional PRIMARY KEY (codtributacaonacional),
    CONSTRAINT tbltributacaonacional_codigo_check CHECK (codigo ~ '^[0-9]{6}$')
);
CREATE UNIQUE INDEX IF NOT EXISTS uk_tbltributacaonacional_codigo ON tbltributacaonacional (codigo);
CREATE INDEX IF NOT EXISTS idx_tbltributacaonacional_item ON tbltributacaonacional (item);

CREATE TABLE IF NOT EXISTS tblnbs (
    codnbs bigserial NOT NULL,
    -- 9 digitos, sem os pontos. Ex.: 1.0101.11.00 = 101011100
    codigo varchar(9) NOT NULL,
    descricao text NOT NULL,
    inativo timestamp(0) without time zone,
    criacao timestamp(0) without time zone DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) without time zone DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT pk_tblnbs PRIMARY KEY (codnbs),
    CONSTRAINT tblnbs_codigo_check CHECK (codigo ~ '^[0-9]{9}$')
);
CREATE UNIQUE INDEX IF NOT EXISTS uk_tblnbs_codigo ON tblnbs (codigo);

CREATE TABLE IF NOT EXISTS tblnbsitem (
    codnbsitem bigserial NOT NULL,
    item varchar(5) NOT NULL,
    codnbs bigint NOT NULL,
    criacao timestamp(0) without time zone DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) without time zone DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT pk_tblnbsitem PRIMARY KEY (codnbsitem)
);
CREATE UNIQUE INDEX IF NOT EXISTS uk_tblnbsitem_item_codnbs ON tblnbsitem (item, codnbs);
CREATE INDEX IF NOT EXISTS idx_tblnbsitem_codnbs ON tblnbsitem (codnbs);

-- ---------------------------------------------------------------------
-- Nota fiscal de servico
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tblnotafiscalservico (
    codnotafiscalservico bigserial NOT NULL,
    codfilial bigint NOT NULL,
    -- tomador (opcional: copia/encadernacao sem cliente identificado)
    codpessoa bigint,
    -- DPS: serie e numero nossos; numero nasce no envio (DIG = sem numero)
    serie integer NOT NULL,
    numero integer,
    -- dhEmi da DPS, gravado no envio
    emissao timestamp(0) without time zone,
    competencia date NOT NULL,
    codtributacaonacional bigint NOT NULL,
    codnbs bigint NOT NULL,
    -- xDescServ (ate 2000)
    descricao varchar(2000) NOT NULL,
    valorservico numeric(14,2) NOT NULL,
    -- abate a base do ISS
    valordescontoincondicionado numeric(14,2),
    -- depende de evento futuro (ex.: pagar ate o dia 10); nao abate a base
    valordescontocondicionado numeric(14,2),
    -- aliquota efetiva do ISS no Simples (muda todo mes), digitada em cada nota;
    -- so vai na DPS com ISS retido (sem retencao o Sistema Nacional recusa: E0625)
    issaliquota numeric(5,2),
    issretido boolean NOT NULL DEFAULT false,
    -- % aproximado dos tributos do Simples (pTotTribSN), digitado em cada nota;
    -- obrigatorio para ME/EPP (E0712: "nao informar" nao e' aceito)
    tributospercentual numeric(4,2),
    -- local da prestacao (padrao: cidade da filial)
    codcidadeprestacao bigint NOT NULL,
    -- xInfComp (ate 2000)
    observacoes varchar(2000),
    -- DIG em digitacao, ERR nao autorizada, AUT autorizada, CAN cancelada
    status varchar(3) NOT NULL DEFAULT 'DIG',
    -- NFS-e devolvida pelo Sistema Nacional
    nfsechave varchar(50),
    nfsenumero bigint,
    nfseautorizacao timestamp(0) without time zone,
    nfsecancelamento timestamp(0) without time zone,
    -- justificativa do cancelamento
    justificativa varchar(255),
    -- ultima mensagem do Sistema Nacional (rejeicao ou erro de comunicacao)
    mensagem text,
    criacao timestamp(0) without time zone DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) without time zone DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT pk_tblnotafiscalservico PRIMARY KEY (codnotafiscalservico),
    CONSTRAINT tblnotafiscalservico_status_check CHECK (status IN ('DIG', 'ERR', 'AUT', 'CAN')),
    CONSTRAINT tblnotafiscalservico_valorservico_check CHECK (valorservico > 0)
);
CREATE UNIQUE INDEX IF NOT EXISTS uk_tblnotafiscalservico_filial_serie_numero
    ON tblnotafiscalservico (codfilial, serie, numero) WHERE numero IS NOT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS uk_tblnotafiscalservico_nfsechave
    ON tblnotafiscalservico (nfsechave) WHERE nfsechave IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_tblnotafiscalservico_codfilial ON tblnotafiscalservico (codfilial);
CREATE INDEX IF NOT EXISTS idx_tblnotafiscalservico_codpessoa ON tblnotafiscalservico (codpessoa);
CREATE INDEX IF NOT EXISTS idx_tblnotafiscalservico_emissao ON tblnotafiscalservico (emissao);
CREATE INDEX IF NOT EXISTS idx_tblnotafiscalservico_status ON tblnotafiscalservico (status);

-- ---------------------------------------------------------------------
-- Distribuicao do ADN (caixa de DF-e da NFS-e)
-- ---------------------------------------------------------------------
-- Registro fiel do que o ADN entregou, uma linha por NSU da filial (o maior NSU
-- e' de onde a leitura continua). Estrutura propria, fora da tbldistribuicaodfe:
-- o NSU do ADN e' outra sequencia. O XML fica em disco (nfse-dfe/), gzipado.
CREATE TABLE IF NOT EXISTS tbldistribuicaonfse (
    coddistribuicaonfse bigserial NOT NULL,
    codfilial bigint NOT NULL,
    nsu bigint NOT NULL,
    -- NFSE ou EVENTO
    tipodocumento varchar(20) NOT NULL,
    -- texto do ADN: CANCELAMENTO, SUBSTITUICAO, CONFIRMACAO_TOMADOR...
    tipoevento varchar(50),
    nfsechave varchar(50),
    -- DataHoraGeracao do ADN
    data timestamp(0) without time zone,
    -- lidos do XML da NFS-e (para a lista)
    nfsenumero bigint,
    emitentecnpj varchar(14),
    emitente varchar(300),
    tomadorcnpj varchar(14),
    tomador varchar(300),
    valorservico numeric(14,2),
    -- quando a NFS-e e' nossa
    codnotafiscalservico bigint,
    criacao timestamp(0) without time zone DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) without time zone DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT pk_tbldistribuicaonfse PRIMARY KEY (coddistribuicaonfse)
);
CREATE UNIQUE INDEX IF NOT EXISTS uk_tbldistribuicaonfse_filial_nsu ON tbldistribuicaonfse (codfilial, nsu);
CREATE INDEX IF NOT EXISTS idx_tbldistribuicaonfse_nfsechave ON tbldistribuicaonfse (nfsechave);
CREATE INDEX IF NOT EXISTS idx_tbldistribuicaonfse_data ON tbldistribuicaonfse (data);
CREATE INDEX IF NOT EXISTS idx_tbldistribuicaonfse_codnotafiscalservico ON tbldistribuicaonfse (codnotafiscalservico);

-- Numeracao da DPS da FDF (as demais series nascem no primeiro envio)
CREATE SEQUENCE IF NOT EXISTS tblnotafiscalservico_numero_201_1_seq;

-- ---------------------------------------------------------------------
-- Chaves estrangeiras
-- ---------------------------------------------------------------------
DO $$
DECLARE
    fk record;
BEGIN
    FOR fk IN
        SELECT * FROM (VALUES
            ('tbltributacaonacional', 'fk_tbltributacaonacional_tblusuario', 'codusuariocriacao', 'tblusuario', 'codusuario'),
            ('tbltributacaonacional', 'fk_tbltributacaonacional_tblusuario_0', 'codusuarioalteracao', 'tblusuario', 'codusuario'),
            ('tblnbs', 'fk_tblnbs_tblusuario', 'codusuariocriacao', 'tblusuario', 'codusuario'),
            ('tblnbs', 'fk_tblnbs_tblusuario_0', 'codusuarioalteracao', 'tblusuario', 'codusuario'),
            ('tblnbsitem', 'fk_tblnbsitem_tblnbs', 'codnbs', 'tblnbs', 'codnbs'),
            ('tblnbsitem', 'fk_tblnbsitem_tblusuario', 'codusuariocriacao', 'tblusuario', 'codusuario'),
            ('tblnbsitem', 'fk_tblnbsitem_tblusuario_0', 'codusuarioalteracao', 'tblusuario', 'codusuario'),
            ('tblnotafiscalservico', 'fk_tblnotafiscalservico_tblfilial', 'codfilial', 'tblfilial', 'codfilial'),
            ('tblnotafiscalservico', 'fk_tblnotafiscalservico_tblpessoa', 'codpessoa', 'tblpessoa', 'codpessoa'),
            ('tblnotafiscalservico', 'fk_tblnotafiscalservico_tbltributacaonacional', 'codtributacaonacional', 'tbltributacaonacional', 'codtributacaonacional'),
            ('tblnotafiscalservico', 'fk_tblnotafiscalservico_tblnbs', 'codnbs', 'tblnbs', 'codnbs'),
            ('tblnotafiscalservico', 'fk_tblnotafiscalservico_tblcidade', 'codcidadeprestacao', 'tblcidade', 'codcidade'),
            ('tblnotafiscalservico', 'fk_tblnotafiscalservico_tblusuario', 'codusuariocriacao', 'tblusuario', 'codusuario'),
            ('tblnotafiscalservico', 'fk_tblnotafiscalservico_tblusuario_0', 'codusuarioalteracao', 'tblusuario', 'codusuario'),
            ('tbldistribuicaonfse', 'fk_tbldistribuicaonfse_tblfilial', 'codfilial', 'tblfilial', 'codfilial'),
            ('tbldistribuicaonfse', 'fk_tbldistribuicaonfse_tblnotafiscalservico', 'codnotafiscalservico', 'tblnotafiscalservico', 'codnotafiscalservico'),
            ('tbldistribuicaonfse', 'fk_tbldistribuicaonfse_tblusuario', 'codusuariocriacao', 'tblusuario', 'codusuario'),
            ('tbldistribuicaonfse', 'fk_tbldistribuicaonfse_tblusuario_0', 'codusuarioalteracao', 'tblusuario', 'codusuario')
        ) AS t(tabela, nome, coluna, ref, refcoluna)
    LOOP
        IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = fk.nome) THEN
            EXECUTE format(
                'ALTER TABLE %I ADD CONSTRAINT %I FOREIGN KEY (%I) '
                'REFERENCES %I (%I) ON UPDATE CASCADE',
                fk.tabela, fk.nome, fk.coluna, fk.ref, fk.refcoluna
            );
        END IF;
    END LOOP;
END $$;

COMMIT;

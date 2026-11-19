-- =====================================================================
-- Caixa de DF-e da NFS-e (TASK-279): nossa emissao x emitida por terceiro.
--
--   tbldistribuicaonfse.emitida — true quando a filial e' a EMITENTE da
--                                 NFS-e, como a tblnotafiscal.emitida.
--                                 False: emitida por outro contra a filial
--                                 (tomadora ou, raro, intermediaria).
--
-- A chave da NFS-e (50 digitos) leva o CNPJ/CPF do emitente nos digitos 10
-- a 23: municipio (7) + ambiente (1) + tipo de inscricao (1) + inscricao (14)
-- + numero (13) + AAMM (4) + codigo (9) + DV (1). Vale para os eventos, que
-- so tem a chave. Novos documentos: NfseDistribuicaoService::processar.
--
-- Rodar ANTES do deploy: sem a coluna o processar falha e o robo
-- nfse:dist-dfe para de gravar a caixa.
--
-- Idempotente (IF NOT EXISTS; o UPDATE so mexe em quem ainda esta false).
-- Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '120s';

ALTER TABLE tbldistribuicaonfse ADD COLUMN IF NOT EXISTS emitida boolean NOT NULL DEFAULT false;

-- Linhas que ja estao na caixa
UPDATE tbldistribuicaonfse d
SET emitida = true
FROM tblfilial f
JOIN tblpessoa p ON p.codpessoa = f.codpessoa
WHERE f.codfilial = d.codfilial
  AND d.emitida = false
  AND substring(d.nfsechave from 10 for 14) = lpad(p.cnpj::bigint::text, 14, '0');

COMMIT;

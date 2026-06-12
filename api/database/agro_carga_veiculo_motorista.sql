-- Vincula a carga de colheita ao cadastro de veículo (caminhão) e à pessoa
-- (motorista). As colunas de texto placa/motorista seguem existindo como
-- snapshot — preenchem o ticket e cobrem o fluxo offline (placa nova ainda
-- não cadastrada, ou motorista digitado sem vínculo). Ambas FKs opcionais.
--
-- Tipo bigint: as PKs referenciadas (tblveiculo.codveiculo e tblpessoa.codpessoa)
-- são bigint, então as colunas FK acompanham (evita overflow e bate com a
-- convenção das tabelas fiscais).
-- Idempotente: IF NOT EXISTS pula se a coluna já existir (sem erro).
BEGIN;
ALTER TABLE tblcargacolheita ADD COLUMN IF NOT EXISTS codveiculo bigint REFERENCES tblveiculo(codveiculo);
ALTER TABLE tblcargacolheita ADD COLUMN IF NOT EXISTS codpessoamotorista bigint REFERENCES tblpessoa(codpessoa);
COMMIT;

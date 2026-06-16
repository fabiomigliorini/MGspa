-- =====================================================================
-- Fase 2 — Campos comerciais do contrato de venda (agro).
-- Produtor vendedor (filial), datas, embarque (janela inicio/fim), portador
-- que recebe, corretora + comissao (info gerencial), cooperativa (Cooasin) e
-- numero do contrato em cada ponta (comprador/corretora/cooperativa).
-- Reaplicavel (ADD COLUMN IF NOT EXISTS).
-- =====================================================================

BEGIN;

ALTER TABLE tblcontrato
  ADD COLUMN IF NOT EXISTS codfilial            integer REFERENCES tblfilial(codfilial),
  ADD COLUMN IF NOT EXISTS datacontrato         date,
  ADD COLUMN IF NOT EXISTS embarqueinicio       date,
  ADD COLUMN IF NOT EXISTS embarquefim          date,
  ADD COLUMN IF NOT EXISTS codportador          integer REFERENCES tblportador(codportador),
  ADD COLUMN IF NOT EXISTS codpessoacorretora   integer REFERENCES tblpessoa(codpessoa),
  ADD COLUMN IF NOT EXISTS comissaotipo         varchar(10),   -- PERCENTUAL | SACA | TOTAL
  ADD COLUMN IF NOT EXISTS comissaovalor        numeric(14,4),
  ADD COLUMN IF NOT EXISTS comissaototal        numeric(14,2), -- valor R$ total resolvido
  ADD COLUMN IF NOT EXISTS viacooperativa       boolean NOT NULL DEFAULT false,
  ADD COLUMN IF NOT EXISTS codpessoacooperativa integer REFERENCES tblpessoa(codpessoa),
  ADD COLUMN IF NOT EXISTS numerocomprador      varchar(30),
  ADD COLUMN IF NOT EXISTS numerocorretora      varchar(30),
  ADD COLUMN IF NOT EXISTS numerocooperativa    varchar(30);

ALTER TABLE tblcontrato
  DROP CONSTRAINT IF EXISTS chk_contrato_comissaotipo;
ALTER TABLE tblcontrato
  ADD CONSTRAINT chk_contrato_comissaotipo
  CHECK (comissaotipo IS NULL OR comissaotipo IN ('PERCENTUAL', 'SACA', 'TOTAL'));

COMMIT;

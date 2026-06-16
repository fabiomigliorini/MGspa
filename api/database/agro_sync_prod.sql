-- =============================================================================
-- SINCRONIZA o schema/dados do AGRO na PRODUÇÃO com o ambiente de DEV.
-- Rodar UMA vez na prod. Idempotente (pode rodar de novo sem efeito colateral).
-- =============================================================================
-- PRÉ-REQUISITO: a prod já deve ter o módulo agro instalado (tabelas tblcultura,
-- tblcargacolheita, tbltabeladesconto) e as tabelas fiscais tblveiculo/tblpessoa.
-- Se a prod NÃO tem o agro ainda (deploy do zero), avise — monto o pacote completo
-- com todos os agro_*.sql (CREATE TABLE) antes deste sync.
--
-- Robustez: a cultura é resolvida pelo NOME (Milho/Soja), não pelo codcultura, que
-- é serial e pode diferir entre dev e prod. Se a cultura não existir na prod, as
-- faixas dela simplesmente não são inseridas (sem erro).
--
-- As faixas de desconto são DADOS DE TESTE (ilustrativos). Substitua pelos valores
-- comerciais reais antes do uso definitivo em produção.
-- =============================================================================
BEGIN;

-- 1) SCHEMA — colunas novas (idempotente) -------------------------------------
-- 1a) emoji da cultura (do bug inicial do ícone)
ALTER TABLE tblcultura ADD COLUMN IF NOT EXISTS icone varchar(8);
-- 1b) vínculo da carga com veículo (caminhão) e pessoa (motorista)
ALTER TABLE tblcargacolheita ADD COLUMN IF NOT EXISTS codveiculo bigint REFERENCES tblveiculo(codveiculo);
ALTER TABLE tblcargacolheita ADD COLUMN IF NOT EXISTS codpessoamotorista bigint REFERENCES tblpessoa(codpessoa);

-- 2) ÍCONES das culturas (igual ao dev) ---------------------------------------
UPDATE tblcultura SET icone = '🌽' WHERE lower(trim(cultura)) = 'milho';
UPDATE tblcultura SET icone = '🌱' WHERE lower(trim(cultura)) = 'soja';

-- 3) TABELAS DE DESCONTO (dados de teste) -------------------------------------
-- Limpa as faixas de Milho/Soja (dedupe) e reinsere o conjunto limpo (49 faixas).
DELETE FROM tbltabeladesconto
 WHERE codcultura IN (SELECT codcultura FROM tblcultura WHERE lower(trim(cultura)) IN ('milho','soja'));

INSERT INTO tbltabeladesconto
  (codcultura, tipo, faixainicio, faixafim, percentualdesconto, criacao, alteracao)
SELECT c.codcultura, v.tipo, v.ini, v.fim, v.pct, now(), now()
FROM tblcultura c
JOIN (VALUES
  -- ===== SOJA ===== (umidade base 14% · impureza 1% · avariados 8% · esverd. 8% · quebr. 30%)
  ('soja','UMIDADE',     14,  15,  1.5),
  ('soja','UMIDADE',     15,  16,  3.0),
  ('soja','UMIDADE',     16,  17,  4.5),
  ('soja','UMIDADE',     17,  18,  6.0),
  ('soja','UMIDADE',     18,  20,  8.0),
  ('soja','UMIDADE',     20,  25, 11.0),
  ('soja','UMIDADE',     25,  40, 15.0),
  ('soja','IMPUREZA',     1,   2,  1.0),
  ('soja','IMPUREZA',     2,   3,  2.0),
  ('soja','IMPUREZA',     3,   4,  3.0),
  ('soja','IMPUREZA',     4,   6,  5.0),
  ('soja','IMPUREZA',     6,  10,  9.0),
  ('soja','IMPUREZA',    10, 100, 15.0),
  ('soja','AVARIADOS',    8,  10,  1.5),
  ('soja','AVARIADOS',   10,  12,  3.0),
  ('soja','AVARIADOS',   12,  16,  6.0),
  ('soja','AVARIADOS',   16,  20, 10.0),
  ('soja','AVARIADOS',   20, 100, 15.0),
  ('soja','ESVERDEADOS',  8,  10,  1.0),  -- NÃO aplicado ainda (sem coluna/cálculo)
  ('soja','ESVERDEADOS', 10,  15,  2.5),
  ('soja','ESVERDEADOS', 15, 100,  5.0),
  ('soja','QUEBRADOS',   30,  40,  1.0),  -- NÃO aplicado ainda
  ('soja','QUEBRADOS',   40,  60,  2.5),
  ('soja','QUEBRADOS',   60, 100,  5.0),
  -- ===== MILHO ===== (avariados tolerância ~6% tipo 1)
  ('milho','UMIDADE',     14,  15,  1.5),
  ('milho','UMIDADE',     15,  16,  3.0),
  ('milho','UMIDADE',     16,  17,  4.5),
  ('milho','UMIDADE',     17,  18,  6.0),
  ('milho','UMIDADE',     18,  20,  8.0),
  ('milho','UMIDADE',     20,  25, 11.0),
  ('milho','UMIDADE',     25,  40, 15.0),
  ('milho','IMPUREZA',     1,   2,  1.0),
  ('milho','IMPUREZA',     2,   3,  2.0),
  ('milho','IMPUREZA',     3,   4,  3.0),
  ('milho','IMPUREZA',     4,   6,  5.0),
  ('milho','IMPUREZA',     6,  10,  9.0),
  ('milho','IMPUREZA',    10, 100, 15.0),
  ('milho','AVARIADOS',    6,   8,  1.5),
  ('milho','AVARIADOS',    8,  10,  3.0),
  ('milho','AVARIADOS',   10,  15,  6.0),
  ('milho','AVARIADOS',   15,  20, 10.0),
  ('milho','AVARIADOS',   20, 100, 15.0),
  ('milho','ESVERDEADOS',  5,  10,  1.0),  -- NÃO aplicado ainda
  ('milho','ESVERDEADOS', 10,  20,  3.0),
  ('milho','ESVERDEADOS', 20, 100,  6.0),
  ('milho','QUEBRADOS',    5,  10,  1.0),  -- NÃO aplicado ainda
  ('milho','QUEBRADOS',   10,  20,  2.5),
  ('milho','QUEBRADOS',   20,  40,  5.0),
  ('milho','QUEBRADOS',   40, 100,  8.0)
) AS v(cult, tipo, ini, fim, pct) ON lower(trim(c.cultura)) = v.cult;

COMMIT;

-- =============================================================================
-- Seed de TABELAS DE DESCONTO para TESTE — Milho (codcultura=3) e Soja (codcultura=4)
-- =============================================================================
-- Valores ILUSTRATIVOS para testar o projeto. Ancorados nas normas MAPA:
--   Soja  (IN 11/2007): umidade base 14% · impureza+mat.estranhas <=1% · avariados <=8%
--                       · esverdeados <=8% · quebrados+amassados <=30%
--   Milho (IN 60/2011): umidade base 14% · impureza ~1% · avariados ~6% (tipo 1)
-- As tabelas de DESCONTO são COMERCIAIS (cada comprador/cooperativa define a sua) —
-- troque os percentuais pelos valores reais da operação quando for pra produção.
--
-- Como o sistema casa a faixa: aplica a faixa onde  faixainicio <= leitura <= faixafim,
-- e usa o percentualdesconto dela sobre o peso líquido. Leitura ABAIXO da 1ª faixa
-- (ex.: umidade < 14%) não casa nenhuma faixa => desconto 0 (correto). As faixas vão
-- até um teto alto pra nenhuma leitura ficar sem desconto.
--
-- ⚠️ HOJE o sistema só CALCULA desconto de UMIDADE, IMPUREZA e AVARIADOS (são as únicas
--    colunas/cálculo da carga). As faixas de ESVERDEADOS e QUEBRADOS ficam cadastradas,
--    mas NÃO são aplicadas até adicionarmos suporte (colunas na carga + cálculo + UI).
--    Incluí-las aqui deixa tudo pronto pra quando estendermos.
--
-- Idempotente: limpa as faixas dessas culturas antes de reinserir (pode rodar de novo).
-- =============================================================================
BEGIN;

DELETE FROM tbltabeladesconto WHERE codcultura IN (3, 4);

INSERT INTO tbltabeladesconto
  (codcultura, tipo, faixainicio, faixafim, percentualdesconto, criacao, alteracao)
VALUES
  -- ===================== SOJA (codcultura = 4) =====================
  -- Umidade (base 14%) — secagem + quebra
  (4, 'UMIDADE',      14,  15,  1.5, now(), now()),
  (4, 'UMIDADE',      15,  16,  3.0, now(), now()),
  (4, 'UMIDADE',      16,  17,  4.5, now(), now()),
  (4, 'UMIDADE',      17,  18,  6.0, now(), now()),
  (4, 'UMIDADE',      18,  20,  8.0, now(), now()),
  (4, 'UMIDADE',      20,  25, 11.0, now(), now()),
  (4, 'UMIDADE',      25,  40, 15.0, now(), now()),
  -- Impureza / matérias estranhas (base 1%) — ~1:1
  (4, 'IMPUREZA',      1,   2,  1.0, now(), now()),
  (4, 'IMPUREZA',      2,   3,  2.0, now(), now()),
  (4, 'IMPUREZA',      3,   4,  3.0, now(), now()),
  (4, 'IMPUREZA',      4,   6,  5.0, now(), now()),
  (4, 'IMPUREZA',      6,  10,  9.0, now(), now()),
  (4, 'IMPUREZA',     10, 100, 15.0, now(), now()),
  -- Avariados (tolerância 8%)
  (4, 'AVARIADOS',     8,  10,  1.5, now(), now()),
  (4, 'AVARIADOS',    10,  12,  3.0, now(), now()),
  (4, 'AVARIADOS',    12,  16,  6.0, now(), now()),
  (4, 'AVARIADOS',    16,  20, 10.0, now(), now()),
  (4, 'AVARIADOS',    20, 100, 15.0, now(), now()),
  -- Esverdeados (tolerância 8%) — NÃO aplicado ainda (ver nota acima)
  (4, 'ESVERDEADOS',   8,  10,  1.0, now(), now()),
  (4, 'ESVERDEADOS',  10,  15,  2.5, now(), now()),
  (4, 'ESVERDEADOS',  15, 100,  5.0, now(), now()),
  -- Quebrados/partidos/amassados (tolerância 30%) — NÃO aplicado ainda
  (4, 'QUEBRADOS',    30,  40,  1.0, now(), now()),
  (4, 'QUEBRADOS',    40,  60,  2.5, now(), now()),
  (4, 'QUEBRADOS',    60, 100,  5.0, now(), now()),
  -- ===================== MILHO (codcultura = 3) =====================
  -- Umidade (base 14%)
  (3, 'UMIDADE',      14,  15,  1.5, now(), now()),
  (3, 'UMIDADE',      15,  16,  3.0, now(), now()),
  (3, 'UMIDADE',      16,  17,  4.5, now(), now()),
  (3, 'UMIDADE',      17,  18,  6.0, now(), now()),
  (3, 'UMIDADE',      18,  20,  8.0, now(), now()),
  (3, 'UMIDADE',      20,  25, 11.0, now(), now()),
  (3, 'UMIDADE',      25,  40, 15.0, now(), now()),
  -- Impureza / matérias estranhas (base 1%)
  (3, 'IMPUREZA',      1,   2,  1.0, now(), now()),
  (3, 'IMPUREZA',      2,   3,  2.0, now(), now()),
  (3, 'IMPUREZA',      3,   4,  3.0, now(), now()),
  (3, 'IMPUREZA',      4,   6,  5.0, now(), now()),
  (3, 'IMPUREZA',      6,  10,  9.0, now(), now()),
  (3, 'IMPUREZA',     10, 100, 15.0, now(), now()),
  -- Avariados (tolerância 6% — tipo 1 milho)
  (3, 'AVARIADOS',     6,   8,  1.5, now(), now()),
  (3, 'AVARIADOS',     8,  10,  3.0, now(), now()),
  (3, 'AVARIADOS',    10,  15,  6.0, now(), now()),
  (3, 'AVARIADOS',    15,  20, 10.0, now(), now()),
  (3, 'AVARIADOS',    20, 100, 15.0, now(), now()),
  -- Esverdeados (milho — referencial baixo) — NÃO aplicado ainda
  (3, 'ESVERDEADOS',   5,  10,  1.0, now(), now()),
  (3, 'ESVERDEADOS',  10,  20,  3.0, now(), now()),
  (3, 'ESVERDEADOS',  20, 100,  6.0, now(), now()),
  -- Quebrados (milho) — NÃO aplicado ainda
  (3, 'QUEBRADOS',     5,  10,  1.0, now(), now()),
  (3, 'QUEBRADOS',    10,  20,  2.5, now(), now()),
  (3, 'QUEBRADOS',    20,  40,  5.0, now(), now()),
  (3, 'QUEBRADOS',    40, 100,  8.0, now(), now());

COMMIT;

-- =====================================================================
-- Seed da tabela de desconto (tbltabeladesconto) — PADRAO DE MERCADO.
-- ATENCAO: estes valores VARIAM por comprador/cooperativa. O ponto que
-- mais muda e o FATOR DE UMIDADE por ponto (1,5 a 1,8 %/ponto). Confirme
-- com o seu comprador e ajuste os fatores abaixo.
--
-- Premissas (pesquisa 03/06/2026):
--   umidade base 14% | fator MILHO 1,5 %/ponto, SOJA 1,6 %/ponto
--   impureza tolerancia 1% (fator 1,0)
--   avariados tolerancia MILHO 6% / SOJA 8% (fator 1,0)
--   faixas [inicio, fim) passo 0,5% ; desconto = (fim - base) x fator
-- Fontes: IN MAPA 60/2011 (milho) e 11/2007 (soja); Aprosoja-MS;
--   AGAIS (Lacerda Filho); tabela real COOASAVI (validacao da umidade).
--
-- Esverdeados / Quebrados (pesquisa 09/06/2026, padrao de mercado/ANEC):
--   SOJA esverdeados tolerancia 8% ; quebrados+partidos+amassados tol 30%
--     (IN MAPA 11/2007; limites comerciais ANEC/ABIOVE).
--   MILHO quebrados/quirera tolerancia 5% (IN MAPA 60/2011, Tipo 3 <= 5%).
--   MILHO esverdeados NAO e defeito oficial em milho (IN 60/2011 nao
--     classifica esverdeados) — tabela criada por simetria, tol 8%, AJUSTE
--     conforme o comprador (pode nem usar). Fator 1,0 nos quatro (desconto
--     no peso do excedente acima da tolerancia, igual avariados).
--
-- Reaplicavel: zera e re-semeia. NAO rode se ja tiver faixas ajustadas
-- manualmente que queira preservar.
-- =====================================================================

-- Garante as culturas (idempotente)
INSERT INTO tblcultura (cultura, pesosaca, criacao, alteracao)
SELECT 'Milho', 60, now(), now() WHERE NOT EXISTS (SELECT 1 FROM tblcultura WHERE cultura = 'Milho');
INSERT INTO tblcultura (cultura, pesosaca, criacao, alteracao)
SELECT 'Soja', 60, now(), now() WHERE NOT EXISTS (SELECT 1 FROM tblcultura WHERE cultura = 'Soja');

DELETE FROM tbltabeladesconto;

-- ---------- MILHO (umidade 1,5 %/ponto, avariados tol 6%) ----------
INSERT INTO tbltabeladesconto (codcultura, tipo, faixainicio, faixafim, percentualdesconto, criacao, alteracao)
SELECT c.codcultura, 'UMIDADE', f, f + 0.5, greatest(0, round((f + 0.5 - 14) * 1.5, 3)), now(), now()
FROM tblcultura c, generate_series(13.0::numeric, 29.5::numeric, 0.5) f WHERE c.cultura = 'Milho';

INSERT INTO tbltabeladesconto (codcultura, tipo, faixainicio, faixafim, percentualdesconto, criacao, alteracao)
SELECT c.codcultura, 'IMPUREZA', f, f + 0.5, greatest(0, round((f + 0.5 - 1) * 1.0, 3)), now(), now()
FROM tblcultura c, generate_series(0.0::numeric, 4.5::numeric, 0.5) f WHERE c.cultura = 'Milho';

INSERT INTO tbltabeladesconto (codcultura, tipo, faixainicio, faixafim, percentualdesconto, criacao, alteracao)
SELECT c.codcultura, 'AVARIADOS', f, f + 0.5, greatest(0, round((f + 0.5 - 6) * 1.0, 3)), now(), now()
FROM tblcultura c, generate_series(0.0::numeric, 14.5::numeric, 0.5) f WHERE c.cultura = 'Milho';

-- esverdeados milho (nao oficial; tol 8% por simetria com a soja)
INSERT INTO tbltabeladesconto (codcultura, tipo, faixainicio, faixafim, percentualdesconto, criacao, alteracao)
SELECT c.codcultura, 'ESVERDEADOS', f, f + 0.5, greatest(0, round((f + 0.5 - 8) * 1.0, 3)), now(), now()
FROM tblcultura c, generate_series(0.0::numeric, 14.5::numeric, 0.5) f WHERE c.cultura = 'Milho';

-- quebrados/quirera milho (tol 5%)
INSERT INTO tbltabeladesconto (codcultura, tipo, faixainicio, faixafim, percentualdesconto, criacao, alteracao)
SELECT c.codcultura, 'QUEBRADOS', f, f + 0.5, greatest(0, round((f + 0.5 - 5) * 1.0, 3)), now(), now()
FROM tblcultura c, generate_series(0.0::numeric, 14.5::numeric, 0.5) f WHERE c.cultura = 'Milho';

-- ---------- SOJA (umidade 1,6 %/ponto, avariados tol 8%) ----------
INSERT INTO tbltabeladesconto (codcultura, tipo, faixainicio, faixafim, percentualdesconto, criacao, alteracao)
SELECT c.codcultura, 'UMIDADE', f, f + 0.5, greatest(0, round((f + 0.5 - 14) * 1.6, 3)), now(), now()
FROM tblcultura c, generate_series(13.0::numeric, 29.5::numeric, 0.5) f WHERE c.cultura = 'Soja';

INSERT INTO tbltabeladesconto (codcultura, tipo, faixainicio, faixafim, percentualdesconto, criacao, alteracao)
SELECT c.codcultura, 'IMPUREZA', f, f + 0.5, greatest(0, round((f + 0.5 - 1) * 1.0, 3)), now(), now()
FROM tblcultura c, generate_series(0.0::numeric, 4.5::numeric, 0.5) f WHERE c.cultura = 'Soja';

INSERT INTO tbltabeladesconto (codcultura, tipo, faixainicio, faixafim, percentualdesconto, criacao, alteracao)
SELECT c.codcultura, 'AVARIADOS', f, f + 0.5, greatest(0, round((f + 0.5 - 8) * 1.0, 3)), now(), now()
FROM tblcultura c, generate_series(0.0::numeric, 14.5::numeric, 0.5) f WHERE c.cultura = 'Soja';

-- esverdeados soja (tol 8%)
INSERT INTO tbltabeladesconto (codcultura, tipo, faixainicio, faixafim, percentualdesconto, criacao, alteracao)
SELECT c.codcultura, 'ESVERDEADOS', f, f + 0.5, greatest(0, round((f + 0.5 - 8) * 1.0, 3)), now(), now()
FROM tblcultura c, generate_series(0.0::numeric, 14.5::numeric, 0.5) f WHERE c.cultura = 'Soja';

-- quebrados/partidos/amassados soja (tol 30%)
INSERT INTO tbltabeladesconto (codcultura, tipo, faixainicio, faixafim, percentualdesconto, criacao, alteracao)
SELECT c.codcultura, 'QUEBRADOS', f, f + 0.5, greatest(0, round((f + 0.5 - 30) * 1.0, 3)), now(), now()
FROM tblcultura c, generate_series(28.0::numeric, 49.5::numeric, 0.5) f WHERE c.cultura = 'Soja';

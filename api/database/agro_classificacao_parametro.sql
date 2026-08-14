-- =============================================================================
-- App Agro — Classificação: CÁLCULO DIRETO NO PARÂMETRO (colapsa a tabela)
-- =============================================================================
-- Substitui o par tbltabelaclassificacao/tbltabelaclassificacaoitem por valores
-- direto no parâmetro, agora POR CULTURA:
--
--   tblparametroclassificacao  ganha codcultura + ordem/tolerancia/fator/desagio
--   DROP tbltabelaclassificacaoitem, tbltabelaclassificacao
--   DROP codtabelaclassificacao de tblcultura, tblcontrato e tblcarga
--
-- A FÓRMULA não muda — ela já era a dos institutos e continua no CargaService
-- (autoridade) e no utils/desconto.js (réplica offline):
--
--   NORMALIZADO  (leitura - tolerancia) / (100 - tolerancia)   <- padrão
--   FATOR        (leitura - tolerancia) * fator / 100          <- taxa comercial
--
-- e a cascata do `reduzbase` (impureza -> umidade -> defeitos) reproduz a eq. 07
-- do boletim AGAIS 01/09: DE = 100 - (100-QI)*(100-QU)/100.
--
-- O que muda é a SEMENTE: Umidade passa de FATOR 1,5 (taxa comercial por ponto)
-- para NORMALIZADO com tolerância 14 — a fórmula da IN MAPA 11/2007 (soja) e
-- 60/2011 (milho), conforme a Cartilha de Classificação da Aprosoja-MS.
--
-- Ver também: agro_classificacao.sql (criou tblcargaclassificacao e migrou as
-- colunas legadas umidade/impureza/avariados de tblcarga — aquele continua válido;
-- só a parte de tbltabelaclassificacao virou histórico).
--
-- IDEMPOTENTE: seguro rodar mais de uma vez (IF NOT EXISTS / NOT EXISTS / ON CONFLICT).
--
-- Rodar:
--   docker exec -i mgdb-mgdb-1 psql -U mgsis -d mgsis < database/agro_classificacao_parametro.sql
-- =============================================================================

BEGIN;

-- 1) Parâmetro passa a ser POR CULTURA e a carregar os números ----------------
ALTER TABLE tblparametroclassificacao
    ADD COLUMN IF NOT EXISTS codcultura integer NULL REFERENCES tblcultura (codcultura),
    ADD COLUMN IF NOT EXISTS ordem      integer      NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS tolerancia numeric(6,3) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS fator      numeric(6,3) NOT NULL DEFAULT 0,   -- metodo = FATOR
    ADD COLUMN IF NOT EXISTS desagio    numeric(6,3) NOT NULL DEFAULT 0;   -- metodo = NORMALIZADO

-- 2) Migra o que existir: cada tabela vira um conjunto de parâmetros da sua ---
--    cultura. O parâmetro antigo era global, então DUPLICAMOS por cultura.
--    Só roda se as tabelas antigas ainda existem (2ª execução pula).
DO $$
BEGIN
    IF to_regclass('tbltabelaclassificacaoitem') IS NOT NULL THEN

        -- 2.1) Adota a cultura da tabela PADRÃO no parâmetro global órfão. Cobre o
        --      caso de uma única cultura: não duplica nada, só carimba.
        UPDATE tblparametroclassificacao p
           SET codcultura = t.codcultura,
               ordem      = i.ordem,
               tolerancia = i.tolerancia,
               fator      = i.fator,
               desagio    = i.desagio
          FROM tbltabelaclassificacaoitem i
          JOIN tbltabelaclassificacao t ON t.codtabelaclassificacao = i.codtabelaclassificacao
          JOIN tblcultura c             ON c.codtabelaclassificacao = t.codtabelaclassificacao
         WHERE i.codparametroclassificacao = p.codparametroclassificacao
           AND p.codcultura IS NULL;

        -- 2.2) Demais culturas com tabela padrão: cria a cópia do parâmetro.
        INSERT INTO tblparametroclassificacao
            (codcultura, parametroclassificacao, metodo, reduzbase,
             ordem, tolerancia, fator, desagio, inativo, criacao, alteracao)
        SELECT c.codcultura, p.parametroclassificacao, p.metodo, p.reduzbase,
               i.ordem, i.tolerancia, i.fator, i.desagio, p.inativo, now(), now()
          FROM tblcultura c
          JOIN tbltabelaclassificacao t     ON t.codtabelaclassificacao = c.codtabelaclassificacao
          JOIN tbltabelaclassificacaoitem i ON i.codtabelaclassificacao = t.codtabelaclassificacao
          JOIN tblparametroclassificacao p  ON p.codparametroclassificacao = i.codparametroclassificacao
         WHERE NOT EXISTS (
                   SELECT 1 FROM tblparametroclassificacao x
                    WHERE x.codcultura = c.codcultura
                      AND x.parametroclassificacao = p.parametroclassificacao
               );

        -- 2.3) Parâmetro global que não entrou em nenhuma tabela padrão não tem
        --      cultura a que pertencer — some (o seed do passo 3 recria o que falta).
        DELETE FROM tblparametroclassificacao WHERE codcultura IS NULL;
    END IF;
END $$;

-- 3) Semeia o padrão da norma para cultura que ficou sem parâmetro ------------
--    Soja  — IN MAPA 11/2007 + Cartilha Aprosoja-MS (Tabela 2, Grupo II padrão
--            básico): MEI 1%, umidade 14%, avariados 8%, esverdeados 8%, PQA 30%.
--    Demais — IN MAPA 60/2011 (milho): umidade até 14% e avariados 6% (Tipo 1).
--            A impureza (1%) segue por analogia com a soja.
--
--    Esverdeados e Quebrados NÃO são semeados fora da soja de propósito: os
--    valores do Quadro 1 da IN 60/2011 não foram confirmados, e chutar tolerância
--    de norma é pior que não ter a linha — o operador cadastra na tela quando o
--    classificador confirmar. Mesma razão para conferir a impureza do milho.
INSERT INTO tblparametroclassificacao
    (codcultura, parametroclassificacao, metodo, reduzbase,
     ordem, tolerancia, fator, desagio, criacao, alteracao)
SELECT c.codcultura, v.nome, 'NORMALIZADO', v.reduzbase, v.ordem, v.tolerancia, 0, 0, now(), now()
  FROM tblcultura c
 CROSS JOIN LATERAL (
     VALUES
        -- ordem, nome, tolerância, reduz a base dos seguintes?, vale p/ esta cultura?
        (1, 'Impureza',    1.0, true,  true),
        (2, 'Umidade',    14.0, true,  true),
        (3, 'Avariados',  CASE WHEN lower(c.cultura) LIKE '%soja%' THEN 8.0 ELSE 6.0 END, false, true),
        (4, 'Esverdeados', 8.0, false, lower(c.cultura) LIKE '%soja%'),
        (5, 'Quebrados',  30.0, false, lower(c.cultura) LIKE '%soja%')
 ) AS v(ordem, nome, tolerancia, reduzbase, aplica)
 WHERE c.inativo IS NULL
   AND v.aplica
   AND NOT EXISTS (SELECT 1 FROM tblparametroclassificacao p WHERE p.codcultura = c.codcultura);

-- 4) Trava a integridade -----------------------------------------------------
ALTER TABLE tblparametroclassificacao ALTER COLUMN codcultura SET NOT NULL;

CREATE UNIQUE INDEX IF NOT EXISTS tblparametroclassificacao_cultura_nome_idx
    ON tblparametroclassificacao (codcultura, parametroclassificacao);

-- 5) Aposenta a camada de tabela ---------------------------------------------
ALTER TABLE tblcultura  DROP COLUMN IF EXISTS codtabelaclassificacao;
ALTER TABLE tblcontrato DROP COLUMN IF EXISTS codtabelaclassificacao;
ALTER TABLE tblcarga    DROP COLUMN IF EXISTS codtabelaclassificacao;

DROP TABLE IF EXISTS tbltabelaclassificacaoitem;
DROP TABLE IF EXISTS tbltabelaclassificacao;

COMMIT;

-- Conferência:
-- SELECT c.cultura, p.ordem, p.parametroclassificacao, p.metodo,
--        p.tolerancia, p.fator, p.desagio, p.reduzbase
--   FROM tblparametroclassificacao p
--   JOIN tblcultura c ON c.codcultura = p.codcultura
--  ORDER BY c.cultura, p.ordem;

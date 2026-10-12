-- =====================================================================
-- Tipo de portador (M2 do plano doc-3, fechamento de caixa).
--
--   tblportador.tipo char(1) NOT NULL DEFAULT 'O'
--     E especie (gaveta, cofre, troco, Caixa Financeiro)
--     B banco
--     A adquirente / conta de pagamento (Stone, Safra, Mercado Pago, Asaas)
--     C cartao de credito da empresa (tem fatura)
--     O outros (Carteira, Cobrador Externo, pseudoportadores de historico)
--
-- Gaveta = portador E com PDV apontando (tblpdv.codportador). As gavetas
-- NAO sao criadas aqui: cada uma e' cadastrada no contas (Portadores, tipo
-- Especie) e vinculada ao PDV em negocios -> Config -> PDV.
--
-- Seed do tipo so' roda quando a coluna nasce (depois disso quem manda e' a
-- tela). Inserts de Stone, SafraPay e trocos guardados por nome.
-- Idempotente. Transacional. Pode rodar de novo a vontade.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '60s';

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'tblportador' AND column_name = 'tipo'
    ) THEN
        ALTER TABLE tblportador ADD COLUMN tipo char(1) NOT NULL DEFAULT 'O';

        -- banco: tudo que tem codbanco
        UPDATE tblportador SET tipo = 'B' WHERE codbanco IS NOT NULL;

        -- cartao da empresa: credito = C; debito sai da conta = B
        UPDATE tblportador SET tipo = 'C'
        WHERE portador ILIKE 'cartao%' AND portador NOT ILIKE '%debito%';
        UPDATE tblportador SET tipo = 'B'
        WHERE portador ILIKE 'cartao%' AND portador ILIKE '%debito%';

        -- especie
        UPDATE tblportador SET tipo = 'E'
        WHERE codportador IN (100, 101001, 202002, 201001, 202001, 202023, 202017);

        -- adquirente / conta de pagamento
        UPDATE tblportador SET tipo = 'A'
        WHERE codportador IN (202019, 202027, 202046, 202049);
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'tblportador_tipo_check'
    ) THEN
        ALTER TABLE tblportador
            ADD CONSTRAINT tblportador_tipo_check CHECK (tipo IN ('E', 'B', 'A', 'C', 'O'));
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS tblportador_tipo_idx ON tblportador (tipo);

-- Caixa Arquitetura estava sem filial
UPDATE tblportador SET codfilial = 501 WHERE codportador = 202017 AND codfilial IS NULL;

-- Adquirentes novos
INSERT INTO tblportador (portador, tipo, codpessoa)
SELECT 'Stone', 'A', 9993
WHERE NOT EXISTS (SELECT 1 FROM tblportador WHERE portador = 'Stone');

INSERT INTO tblportador (portador, tipo, codpessoa)
SELECT 'SafraPay', 'A', 20119
WHERE NOT EXISTS (SELECT 1 FROM tblportador WHERE portador = 'SafraPay');

-- Troco de cada loja
INSERT INTO tblportador (portador, tipo, codfilial)
SELECT 'Troco ' || f.filial, 'E', f.codfilial
FROM tblfilial f
WHERE f.codfilial BETWEEN 101 AND 105
  AND NOT EXISTS (
      SELECT 1 FROM tblportador p WHERE p.portador = 'Troco ' || f.filial
  );

-- Conferencia
SELECT tipo, count(*) FROM tblportador GROUP BY tipo ORDER BY tipo;

COMMIT;

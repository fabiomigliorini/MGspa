-- =====================================================================
-- PIX usado ou sem uso (TASK-219).
--
-- O PIX que caiu na conta nao e' pagamento: usar o PIX e' gravar um
-- pagamento com ele (baixa de titulos, adiantamento, a venda do PIX QR).
-- O PIX guarda, no padrao de criacao/alteracao:
--   utilizacao           — quando foi usado (vazio = sem uso)
--   codusuarioutilizacao — quem usou (quem gravou o pagamento ou marcou)
--   codpagamento         — o pagamento que usou (vazio quando marcado 'a mao)
--
-- Os PIX que ja' existem foram resolvidos pelo jeito de antes: entram todos
-- como usados, com o pagamento quando ha' um ligado a eles. Isso so' roda
-- na execucao que cria as colunas (depois, o PIX sem uso e' de verdade).
--
-- Idempotente. Transacional.
-- =====================================================================

\set ON_ERROR_STOP on
BEGIN;

SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '300s';

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_schema = 'mgsis' AND table_name = 'tblpix' AND column_name = 'utilizacao') THEN
        ALTER TABLE mgsis.tblpix
            ADD COLUMN utilizacao timestamp(0) without time zone,
            ADD COLUMN codusuarioutilizacao bigint,
            ADD COLUMN codpagamento bigint;

        -- os PIX de antes: usados
        UPDATE mgsis.tblpix SET utilizacao = coalesce(horario, criacao);

        -- com o pagamento que aponta para o PIX
        UPDATE mgsis.tblpix x
        SET codpagamento = p.codpagamento
        FROM (
            SELECT DISTINCT ON (codpix) codpix, codpagamento
            FROM mgsis.tblpagamento
            WHERE codpix IS NOT NULL AND estado != 'C'
            ORDER BY codpix, codpagamento
        ) p
        WHERE p.codpix = x.codpix;

        -- ou o da cobranca (PIX QR da venda)
        UPDATE mgsis.tblpix x
        SET codpagamento = pg.codpagamento
        FROM mgsis.tblpagamento pg
        WHERE x.codpagamento IS NULL
        AND x.codpixcob IS NOT NULL
        AND pg.codpixcob = x.codpixcob
        AND pg.estado != 'C';
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_tblpix_tblusuario_utilizacao') THEN
        ALTER TABLE mgsis.tblpix ADD CONSTRAINT fk_tblpix_tblusuario_utilizacao
            FOREIGN KEY (codusuarioutilizacao) REFERENCES mgsis.tblusuario(codusuario) ON UPDATE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_tblpix_tblpagamento') THEN
        ALTER TABLE mgsis.tblpix ADD CONSTRAINT fk_tblpix_tblpagamento
            FOREIGN KEY (codpagamento) REFERENCES mgsis.tblpagamento(codpagamento) ON UPDATE CASCADE;
    END IF;
END $$;

-- os sem uso (a lista da tela PIX) e o PIX do pagamento (cancelar devolve o PIX)
CREATE INDEX IF NOT EXISTS idx_tblpix_sem_uso ON tblpix (horario) WHERE utilizacao IS NULL;
CREATE INDEX IF NOT EXISTS idx_tblpix_codpagamento ON tblpix (codpagamento) WHERE codpagamento IS NOT NULL;
-- o PIX que pagou um boleto e o boleto dele, pelo txid do QR do boleto
CREATE INDEX IF NOT EXISTS idx_tblpix_txid ON tblpix (txid);
CREATE INDEX IF NOT EXISTS idx_tbltituloboleto_qrcodetxid ON tbltituloboleto (qrcodetxid) WHERE qrcodetxid IS NOT NULL;

COMMIT;

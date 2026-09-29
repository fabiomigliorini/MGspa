-- =====================================================================
-- App Agro — Contrato: remove tblcontrato.dataembarque (TASK-134)
-- =====================================================================
-- dataembarque (uma data so, "limite/janela de embarque") vem do primeiro modelo
-- do contrato (passo 02). O passo 12 trouxe a janela de verdade —
-- embarqueinicio/embarquefim — e a tela so usa essas duas desde entao
-- (ContratoForm.vue). dataembarque ficou no model e no request, mas nada grava
-- nem le.
--
-- Antes de dropar, a data antiga NAO se perde: vira o fim da janela, e o
-- inicio (se vazio) vira o 1o dia daquele mes — a mesma regra do inicioDoMes()
-- do ContratoForm quando o operador preenche so o fim.
--
-- GUARDA: so roda enquanto a coluna existe.
-- Bloco unico (atomico); sem BEGIN/COMMIT pra nao deixar transacao aberta no DBeaver.
-- =====================================================================

SET lock_timeout = '5s';

DO $agro$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblcontrato' AND column_name = 'dataembarque') THEN
    RAISE NOTICE '28 contrato_dataembarque: pulado (coluna ja removida)';
    RETURN;
  END IF;

  EXECUTE $sql$
    UPDATE tblcontrato
       SET embarquefim    = coalesce(embarquefim, dataembarque),
           embarqueinicio = coalesce(embarqueinicio,
                                     least(date_trunc('month', dataembarque)::date,
                                           coalesce(embarquefim, dataembarque)))
     WHERE dataembarque IS NOT NULL
       AND (embarquefim IS NULL OR embarqueinicio IS NULL)
  $sql$;

  ALTER TABLE tblcontrato DROP COLUMN dataembarque;

  RAISE NOTICE '28 contrato_dataembarque: aplicado';
END
$agro$;

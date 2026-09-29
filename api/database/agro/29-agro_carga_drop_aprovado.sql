-- =====================================================================
-- App Agro — Carga: remove tblcarga.aprovado (TASK-134)
-- =====================================================================
-- "aprovado" (timestamp, "comprador aprovou (saida)") veio do embarque antigo e
-- foi herdado pela carga unificada (passo 13). O fluxo de aprovacao nunca foi
-- implementado: nada grava nem le a coluna — so confundia quem consulta o banco.
--
-- As colunas de classificacao do modelo antigo (umidade, impureza, avariados e
-- os 3 descontos) ja saem no passo 24, migradas para tblcargaclassificacao.
--
-- GUARDA: DROP COLUMN IF EXISTS — rodar de novo nao faz nada.
-- =====================================================================

SET lock_timeout = '5s';

DO $agro$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblcarga' AND column_name = 'aprovado') THEN
    RAISE NOTICE '29 carga_drop_aprovado: pulado (coluna ja removida)';
    RETURN;
  END IF;

  ALTER TABLE tblcarga DROP COLUMN aprovado;

  RAISE NOTICE '29 carga_drop_aprovado: aplicado';
END
$agro$;

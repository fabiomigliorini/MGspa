-- =============================================================================
-- App Agro — Motorista SEM cadastro na carga (TASK-171)
-- =============================================================================
-- O motorista da carga pode ser uma pessoa cadastrada (codpessoamotorista) ou
-- só descrito naquela operação — como o CPF na nota do /negocios sem cadastro
-- (tblnegocio.cpf). No segundo caso a carga guarda o que identifica o
-- motorista: nome (motorista), CPF, celular e endereço. Transportar grão é mais
-- sério que comprar na loja — sem telefone e endereço não se acha o motorista.
-- O endereço é um campo só (rua, número e complemento juntos), como no pátio.
--
-- Com codpessoamotorista preenchido estas colunas ficam NULL: os dados vivem em
-- tblpessoa / tblpessoatelefone / tblpessoaendereco.
--
-- CPF, telefone e CEP em varchar (só dígitos): numeric perde zero à esquerda
-- (a armadilha do tblpessoa.cnpj, que obriga todo mundo a to_char(...)).
-- Quem valida é o front e o CargaSincronizarRequest.
-- =============================================================================
--
-- GUARDA: pula se as 6 colunas existem e o comentario do celular ja e o atual.
-- Quem rodou a versao de 28/09 (numeromotorista/complementomotorista, telefone fixo)
-- ganha aqui os comentarios novos; as 2 colunas extras sao removidas.
-- Bloco unico (atomico); sem BEGIN/COMMIT pra nao deixar transacao aberta no DBeaver.
-- =====================================================================

SET lock_timeout = '5s';

DO $agro$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblcarga' AND column_name = 'cpfmotorista') AND EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblcarga' AND column_name = 'telefonemotorista') AND EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblcarga' AND column_name = 'cepmotorista') AND EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblcarga' AND column_name = 'enderecomotorista') AND EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblcarga' AND column_name = 'bairromotorista') AND EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'tblcarga' AND column_name = 'codcidademotorista') AND col_description('tblcarga'::regclass, (SELECT attnum FROM pg_attribute WHERE attrelid = 'tblcarga'::regclass AND attname = 'telefonemotorista')) = 'Celular (DDD + número, 11 dígitos) do motorista SEM cadastro' THEN
    RAISE NOTICE '27 carga_motorista_sem_cadastro: pulado (colunas e comentarios ja atualizados)';
    RETURN;
  END IF;

  ALTER TABLE tblcarga ADD COLUMN IF NOT EXISTS cpfmotorista varchar(11);
  ALTER TABLE tblcarga ADD COLUMN IF NOT EXISTS telefonemotorista varchar(11);
  ALTER TABLE tblcarga ADD COLUMN IF NOT EXISTS cepmotorista varchar(8);
  ALTER TABLE tblcarga ADD COLUMN IF NOT EXISTS enderecomotorista varchar(100);
  ALTER TABLE tblcarga ADD COLUMN IF NOT EXISTS bairromotorista varchar(50);
  ALTER TABLE tblcarga ADD COLUMN IF NOT EXISTS codcidademotorista bigint REFERENCES tblcidade(codcidade);

  -- Versao de 28/09/2026 tinha numero e complemento separados: agora vao no endereco.
  ALTER TABLE tblcarga DROP COLUMN IF EXISTS numeromotorista;
  ALTER TABLE tblcarga DROP COLUMN IF EXISTS complementomotorista;

  COMMENT ON COLUMN tblcarga.cpfmotorista IS
    'CPF (só dígitos) do motorista SEM cadastro; com cadastro o CPF vive em tblpessoa.cnpj';
  COMMENT ON COLUMN tblcarga.telefonemotorista IS
    'Celular (DDD + número, 11 dígitos) do motorista SEM cadastro';
  COMMENT ON COLUMN tblcarga.codcidademotorista IS
    'Cidade do endereço do motorista SEM cadastro (rua/número em enderecomotorista)';

  RAISE NOTICE '27 carga_motorista_sem_cadastro: aplicado';
END
$agro$;

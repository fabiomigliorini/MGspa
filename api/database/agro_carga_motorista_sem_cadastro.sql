-- =============================================================================
-- App Agro — Motorista SEM cadastro na carga (TASK-171)
-- =============================================================================
-- O motorista da carga pode ser uma pessoa cadastrada (codpessoamotorista) ou
-- só descrito naquela operação — como o CPF na nota do /negocios sem cadastro
-- (tblnegocio.cpf). No segundo caso a carga guarda o que o cadastro guardaria:
-- nome (motorista), CPF, telefone e endereço. Transportar grão é mais sério que
-- comprar na loja — sem telefone e endereço não se identifica o motorista.
--
-- Com codpessoamotorista preenchido estas colunas ficam NULL: os dados vivem em
-- tblpessoa / tblpessoatelefone / tblpessoaendereco.
--
-- CPF, telefone e CEP em varchar (só dígitos): numeric perde zero à esquerda
-- (a armadilha do tblpessoa.cnpj, que obriga todo mundo a to_char(...)).
-- Quem valida é o front e o CargaSincronizarRequest.
-- =============================================================================

ALTER TABLE tblcarga ADD COLUMN IF NOT EXISTS cpfmotorista varchar(11);
ALTER TABLE tblcarga ADD COLUMN IF NOT EXISTS telefonemotorista varchar(11);
ALTER TABLE tblcarga ADD COLUMN IF NOT EXISTS cepmotorista varchar(8);
ALTER TABLE tblcarga ADD COLUMN IF NOT EXISTS enderecomotorista varchar(100);
ALTER TABLE tblcarga ADD COLUMN IF NOT EXISTS numeromotorista varchar(10);
ALTER TABLE tblcarga ADD COLUMN IF NOT EXISTS complementomotorista varchar(50);
ALTER TABLE tblcarga ADD COLUMN IF NOT EXISTS bairromotorista varchar(50);
ALTER TABLE tblcarga ADD COLUMN IF NOT EXISTS codcidademotorista bigint REFERENCES tblcidade(codcidade);

COMMENT ON COLUMN tblcarga.cpfmotorista IS
  'CPF (só dígitos) do motorista SEM cadastro; com cadastro o CPF vive em tblpessoa.cnpj';
COMMENT ON COLUMN tblcarga.telefonemotorista IS
  'Telefone (DDD + número, só dígitos: 10 fixo, 11 celular) do motorista SEM cadastro';
COMMENT ON COLUMN tblcarga.codcidademotorista IS
  'Cidade do endereço do motorista SEM cadastro (endereço completo nas colunas *motorista)';

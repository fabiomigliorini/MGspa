-- Contingencia off-line da NFC-e (tpEmis = 9).
--
-- 1) CONFIGURACAO NA EMPRESA
--
-- contingenciaautomatica nasce false: contingencia tem implicacao fiscal, entao liga-se
-- empresa por empresa, depois de observar os numeros reais na tblsefazcomunicacao.
-- contingenciatolerancia e o teto, em segundos, para considerar uma conversa com a SEFAZ
-- "boa". 10 conversas consecutivas acima disso (ou com erro) entram em contingencia;
-- 20 consecutivas dentro dela voltam para online.

alter table tblempresa
  add column contingenciaautomatica boolean not null default false,
  add column contingenciatolerancia integer not null default 15;

-- 2) CONGELAMENTO DE dhCont/xJust NA NOTA
--
-- O tpEmis faz parte da CHAVE DE ACESSO (Keys::build ... %01d ...). Uma NFC-e emitida
-- offline tem chave terminando em 9; se ela for recriada depois que a empresa voltou ao
-- modo online, o XML sai com tpEmis 1 e a chave MUDA — e o cupom que ja esta na mao do
-- cliente passa a apontar para uma chave que nao existe na SEFAZ.
--
-- Guardando tpemis + contingenciadata + contingenciajustificativa na propria nota, a
-- recriacao do XML fica 100% deterministica e a chave nunca muda. Tambem elimina o null
-- pointer de NFePHPMakeService (dhCont vinha de Empresa->contingenciadata, nula em 6 das
-- 7 empresas) e sobrevive a um segundo episodio de contingencia com notas do primeiro
-- ainda pendentes de transmissao.

alter table tblnotafiscal
  add column contingenciadata timestamp(0),
  add column contingenciajustificativa varchar(256);

-- =============================================================================
-- App Agro — DIAGNOSTICO da base (SO LEITURA). Rodar ANTES dos passos 01-29.
-- =============================================================================
-- Um SELECT so (roda no DBeaver sem transacao). Para cada passo diz se ja esta
-- FEITO ou PENDENTE nesta base, pelo marcador que o proprio script usa na guarda.
-- As linhas ATENCAO contam os dados que um passo pendente vai mexer — ler antes
-- de rodar e mandar o resultado pra conferencia.
--
-- Nao escreve nada: so information_schema/pg_catalog e contagens. As contagens
-- que dependem de colunas que talvez nao existam usam query_to_xml (SQL dinamico
-- so executado quando a coluna existe).
-- =============================================================================

WITH cols AS (
  SELECT table_name AS t, column_name AS c, data_type, is_nullable
    FROM information_schema.columns
   WHERE table_schema = current_schema()
),
tem AS (
  SELECT
    to_regclass('tblcultura') IS NOT NULL                                         AS cultura,
    to_regclass('tblcontrato') IS NOT NULL                                        AS contrato,
    to_regclass('tblcarga') IS NOT NULL                                           AS carga,
    EXISTS (SELECT 1 FROM cols WHERE t = 'tblcontrato' AND c = 'tipo')            AS c_tipo,
    EXISTS (SELECT 1 FROM cols WHERE t = 'tblcontrato' AND c = 'preco')           AS c_preco,
    EXISTS (SELECT 1 FROM cols WHERE t = 'tblcontrato' AND c = 'codnaturezaoperacao') AS c_nf,
    EXISTS (SELECT 1 FROM cols WHERE t = 'tblcontrato' AND c = 'volumeemaberto')  AS c_volume,
    EXISTS (SELECT 1 FROM cols WHERE t = 'tblcontrato' AND c = 'dataembarque')    AS c_dataembarque,
    EXISTS (SELECT 1 FROM cols WHERE t = 'tblcontratopagamento' AND c = 'datarecebido')       AS p_datarecebido,
    EXISTS (SELECT 1 FROM cols WHERE t = 'tblcontratopagamento' AND c = 'codcontratofixacao') AS p_fixacao,
    EXISTS (SELECT 1 FROM cols WHERE t = 'tblcontratopagamento' AND c = 'codcontrato')        AS p_contrato,
    EXISTS (SELECT 1 FROM cols WHERE t = 'tblcontratofixacao' AND c = 'moeda')    AS f_moeda,
    EXISTS (SELECT 1 FROM cols WHERE t = 'tblparametroclassificacao' AND c = 'codcultura') AS pc_cultura
),
-- conta via SQL dinamico (so chamado quando a condicao do CASE for verdadeira)
passo (ordem, passo, status, detalhe) AS (
  SELECT 1, '01 agro', CASE WHEN tem.cultura THEN 'FEITO' ELSE 'PENDENTE' END, 'tblcultura e cadastros base' FROM tem
  UNION ALL SELECT 2, '02 contrato_embarque', CASE WHEN tem.contrato THEN 'FEITO' ELSE 'PENDENTE' END, 'tblcontrato/fixacao/pagamento' FROM tem
  UNION ALL SELECT 3, '03 talhao_geo', CASE WHEN EXISTS (SELECT 1 FROM cols WHERE t = 'tbltalhao' AND c = 'cor') THEN 'FEITO' ELSE 'PENDENTE' END, 'desenho do talhao' FROM tem
  UNION ALL SELECT 4, '04 plantio_geo', CASE WHEN EXISTS (SELECT 1 FROM cols WHERE t = 'tblplantio' AND c = 'codfazenda') AND to_regclass('uk_plantio_safra_fazenda_talhao') IS NOT NULL THEN 'FEITO' ELSE 'PENDENTE' END, 'desenho do plantio por safra' FROM tem
  UNION ALL SELECT 5, '05 safra_ano', CASE WHEN NOT EXISTS (SELECT 1 FROM cols WHERE t = 'tblsafra' AND c = 'datainicio') AND EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'uk_safra_cultura_anoplantio') THEN 'FEITO' ELSE 'PENDENTE' END,
         CASE WHEN EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'tblsafra_codcultura_anoplantio_key') THEN 'so renomeia o UNIQUE da safra' ELSE 'safra em anos' END FROM tem
  UNION ALL SELECT 6, '06 cultura_icone', CASE WHEN EXISTS (SELECT 1 FROM cols WHERE t = 'tblcultura' AND c = 'icone') THEN 'FEITO' ELSE 'PENDENTE' END, 'emoji da cultura' FROM tem
  UNION ALL SELECT 7, '07 plantio_area_unica', CASE WHEN NOT EXISTS (SELECT 1 FROM cols WHERE t = 'tblplantio' AND c = 'area') THEN 'FEITO' ELSE 'PENDENTE' END, 'remove tblplantio.area' FROM tem
  UNION ALL SELECT 8, '08 plantio_expectativa', CASE WHEN EXISTS (SELECT 1 FROM cols WHERE t = 'tblplantio' AND c = 'expectativasacas') THEN 'FEITO' ELSE 'PENDENTE' END, 'expectativa em sacas' FROM tem
  UNION ALL SELECT 9, '09 contrato_fixacao_automatica', CASE WHEN NOT tem.c_preco THEN 'FEITO' ELSE 'PENDENTE' END, 'preco do FIXO vira fixacao' FROM tem
  UNION ALL SELECT 10, '10 tributacao_contrato', CASE WHEN to_regclass('tblculturatributo') IS NOT NULL AND EXISTS (SELECT 1 FROM cols WHERE t = 'tblfilial' AND c = 'funruralvenda') THEN 'FEITO' ELSE 'PENDENTE' END, 'FETHAB/IAGRO/Funrural/Senar' FROM tem
  UNION ALL SELECT 11, '11 contrato_parcela', CASE WHEN EXISTS (SELECT 1 FROM cols WHERE t = 'tblcontratopagamento' AND c = 'modo') OR NOT tem.p_contrato THEN 'FEITO' ELSE 'PENDENTE' END, 'parcelas previsto x recebido' FROM tem
  UNION ALL SELECT 12, '12 contrato_comercial', CASE WHEN EXISTS (SELECT 1 FROM cols WHERE t = 'tblcontrato' AND c = 'numerocorretora') THEN 'FEITO' ELSE 'PENDENTE' END, 'campos comerciais do contrato' FROM tem
  UNION ALL SELECT 13, '13 grao', CASE WHEN tem.carga AND to_regclass('tblcargacolheita') IS NULL AND to_regclass('tblembarque') IS NULL
                                        AND (SELECT data_type FROM cols WHERE t = 'tblcarga' AND c = 'uuid') = 'uuid' THEN 'FEITO' ELSE 'PENDENTE' END, 'carga unificada + extrato de grao' FROM tem
  UNION ALL SELECT 14, '14 contrato_refatoracao', CASE WHEN NOT tem.c_tipo THEN 'FEITO' ELSE 'PENDENTE' END, 'preco/tipo/NF saem do contrato' FROM tem
  UNION ALL SELECT 15, '15 drop_viacooperativa', CASE WHEN NOT EXISTS (SELECT 1 FROM cols WHERE t = 'tblcontrato' AND c = 'viacooperativa') THEN 'FEITO' ELSE 'PENDENTE' END, '' FROM tem
  UNION ALL SELECT 16, '16 numerocontraparte_rename', CASE WHEN NOT EXISTS (SELECT 1 FROM cols WHERE t = 'tblcontrato' AND c = 'numerocomprador') THEN 'FEITO' ELSE 'PENDENTE' END, '' FROM tem
  UNION ALL SELECT 17, '17 moeda', CASE WHEN EXISTS (SELECT 1 FROM cols WHERE t = 'tblmoeda' AND c = 'codmoeda') THEN 'FEITO' ELSE 'PENDENTE' END, 'tblmoeda com codmoeda' FROM tem
  UNION ALL SELECT 18, '18 fixacao_impostos', CASE WHEN EXISTS (SELECT 1 FROM cols WHERE t = 'tblcontratofixacao' AND c = 'tributos') THEN 'FEITO' ELSE 'PENDENTE' END, '' FROM tem
  UNION ALL SELECT 19, '19 contrato_barter', CASE WHEN EXISTS (SELECT 1 FROM cols WHERE t = 'tblcontrato' AND c = 'barter') THEN 'FEITO' ELSE 'PENDENTE' END, '' FROM tem
  UNION ALL SELECT 20, '20 pagamento_fixacao', CASE WHEN tem.p_fixacao OR NOT tem.p_contrato THEN 'FEITO' ELSE 'PENDENTE' END, 'parcela ligada a uma fixacao' FROM tem
  UNION ALL SELECT 21, '21 fixacao_cambio', CASE WHEN NOT tem.f_moeda AND to_regclass('tblcontratofixacaocambio') IS NOT NULL THEN 'FEITO' ELSE 'PENDENTE' END, 'trava de cambio + totais' FROM tem
  UNION ALL SELECT 22, '22 contrato_recebimento', CASE WHEN NOT tem.p_datarecebido AND EXISTS (SELECT 1 FROM cols WHERE t = 'tblcontratofixacao' AND c = 'quitado') THEN 'FEITO' ELSE 'PENDENTE' END, 'pagamento vira recebimento' FROM tem
  UNION ALL SELECT 23, '23 plantio_hacolhido', CASE WHEN EXISTS (SELECT 1 FROM cols WHERE t = 'tblplantio' AND c = 'hacolhido') THEN 'FEITO' ELSE 'PENDENTE' END, '' FROM tem
  UNION ALL SELECT 24, '24 classificacao', CASE WHEN tem.pc_cultura OR to_regclass('tbltabelaclassificacaoitem') IS NOT NULL THEN 'FEITO' ELSE 'PENDENTE' END, 'leituras da carga em tblcargaclassificacao' FROM tem
  UNION ALL SELECT 25, '25 classificacao_parametro', CASE WHEN tem.pc_cultura AND to_regclass('tbltabelaclassificacao') IS NULL
                                        AND (SELECT is_nullable FROM cols WHERE t = 'tblparametroclassificacao' AND c = 'codcultura') = 'NO' THEN 'FEITO' ELSE 'PENDENTE' END, 'parametro de classificacao por cultura' FROM tem
  UNION ALL SELECT 26, '26 carga_listagem', CASE WHEN to_regclass('ix_carga_safra_data') IS NOT NULL AND to_regclass('ix_cargaponto_contrato') IS NOT NULL THEN 'FEITO' ELSE 'PENDENTE' END, 'indices da listagem de romaneios' FROM tem
  UNION ALL SELECT 27, '27 carga_motorista_sem_cadastro', CASE WHEN EXISTS (SELECT 1 FROM cols WHERE t = 'tblcarga' AND c = 'codcidademotorista') AND NOT EXISTS (SELECT 1 FROM cols WHERE t = 'tblcarga' AND c = 'numeromotorista') THEN 'FEITO' ELSE 'PENDENTE' END, 'motorista sem cadastro' FROM tem
  UNION ALL SELECT 28, '28 contrato_dataembarque', CASE WHEN NOT tem.c_dataembarque THEN 'FEITO' ELSE 'PENDENTE' END, 'dataembarque vira janela embarqueinicio/fim' FROM tem
  UNION ALL SELECT 29, '29 carga_drop_aprovado', CASE WHEN tem.carga AND NOT EXISTS (SELECT 1 FROM cols WHERE t = 'tblcarga' AND c = 'aprovado') THEN 'FEITO' ELSE 'PENDENTE' END, 'remove tblcarga.aprovado' FROM tem
),
conta (ordem, passo, status, detalhe) AS (
  -- Pre-requisitos
  SELECT -3, 'PRE tbltributo', CASE WHEN to_regclass('tbltributo') IS NOT NULL THEN 'OK' ELSE 'FALTA' END, 'catalogo de tributos (passo 10 precisa)' FROM tem
  UNION ALL SELECT -2, 'PRE estado 8956 = MT', coalesce((SELECT CASE WHEN sigla = 'MT' THEN 'OK' ELSE 'CONFERIR' END FROM tblestado WHERE codestado = 8956), 'FALTA'), 'UPF-MT do passo 10 usa codestado 8956' FROM tem
  UNION ALL SELECT -1, 'PRE transacoes abertas', CASE WHEN count(*) = 0 THEN 'OK' ELSE 'CONFERIR' END, count(*) || ' conexao(oes) idle in transaction (seguram lock e travam os ALTER)'
    FROM pg_stat_activity WHERE state LIKE 'idle in transaction%' AND pid <> pg_backend_pid()
  -- O que os passos pendentes vao mexer
  UNION ALL SELECT 110, 'ATENCAO 09/14 contratos com preco', 'INFO',
    CASE WHEN tem.c_preco THEN (xpath('/row/n/text()', query_to_xml(
      'select count(*) filter (where preco is not null) || '' com preco; '' || count(*) filter (where tipo = ''FIXO'' and not exists (select 1 from tblcontratofixacao f where f.codcontrato = c.codcontrato)) || '' FIXO sem fixacao (o 09 cria)'' as n from tblcontrato c', false, true, '')))[1]::text
    ELSE 'ja refatorado' END FROM tem
  UNION ALL SELECT 111, 'ATENCAO 14 contratos com NF/barter/volume', 'INFO',
    CASE WHEN tem.c_nf THEN (xpath('/row/n/text()', query_to_xml(
      'select count(*) filter (where codnaturezaoperacao is not null or codpessoanf is not null or observacaonf is not null) || '' com NF (viram tblcontratonota); '' || count(*) filter (where tipo = ''BARTER'') || '' BARTER'' as n from tblcontrato', false, true, '')))[1]::text
    ELSE 'ja refatorado' END FROM tem
  UNION ALL SELECT 112, 'ATENCAO 20/22 pagamentos', CASE WHEN tem.p_datarecebido THEN 'CONFERIR' ELSE 'INFO' END,
    CASE WHEN tem.p_datarecebido AND tem.p_fixacao THEN (xpath('/row/n/text()', query_to_xml(
      'select count(*) || '' pagamentos; '' || count(*) filter (where datarecebido is null) || '' so previstos e '' || count(*) filter (where datarecebido is not null and codcontratofixacao is null) || '' RECEBIDOS sem fixacao sao DESCARTADOS pelo 22 (ficam no backup)'' as n from tblcontratopagamento', false, true, '')))[1]::text
    WHEN tem.p_datarecebido AND tem.c_tipo THEN (xpath('/row/n/text()', query_to_xml(
      'select count(*) || '' pagamentos; '' || count(*) filter (where p.datarecebido is null) || '' so previstos (descartados pelo 22); RECEBIDOS que o 20 nao consegue ligar a UMA fixacao e o 22 descarta: '' || count(*) filter (where p.datarecebido is not null and (nf.n > 1 or (nf.n = 0 and c.tipo <> ''FIXO''))) || '' (escolher a fixacao a mao antes do 22)'' as n
         from tblcontratopagamento p join tblcontrato c on c.codcontrato = p.codcontrato
         cross join lateral (select count(*) as n from tblcontratofixacao f where f.codcontrato = p.codcontrato and f.inativo is null) nf', false, true, '')))[1]::text
    WHEN tem.p_datarecebido THEN (xpath('/row/n/text()', query_to_xml(
      'select count(*) || '' pagamentos; '' || count(*) filter (where p.datarecebido is null) || '' so previstos (descartados pelo 22); RECEBIDOS que o 20 nao consegue ligar a UMA fixacao e o 22 descarta: '' || count(*) filter (where p.datarecebido is not null and nf.n <> 1) || '' (escolher a fixacao a mao antes do 22)'' as n
         from tblcontratopagamento p
         cross join lateral (select count(*) as n from tblcontratofixacao f where f.codcontrato = p.codcontrato and f.inativo is null) nf', false, true, '')))[1]::text
    ELSE 'ja refatorado' END FROM tem
  UNION ALL SELECT 113, 'ATENCAO 13 carga antiga', CASE WHEN to_regclass('tblcargacolheita') IS NOT NULL OR to_regclass('tblembarque') IS NOT NULL THEN 'CONFERIR' ELSE 'INFO' END,
    CASE WHEN to_regclass('tblcargacolheita') IS NOT NULL THEN (xpath('/row/n/text()', query_to_xml('select count(*) || '' linhas em tblcargacolheita (modelo antigo, o 13 APAGA)'' as n from tblcargacolheita', false, true, '')))[1]::text
         ELSE 'sem modelo antigo' END FROM tem
  UNION ALL SELECT 114, 'ATENCAO 28 dataembarque', 'INFO',
    CASE WHEN tem.c_dataembarque THEN (xpath('/row/n/text()', query_to_xml(
      'select count(*) filter (where dataembarque is not null) || '' contratos com dataembarque; '' || count(*) filter (where dataembarque is not null and (embarquefim is null or embarqueinicio is null)) || '' completam a janela com ela'' as n from tblcontrato', false, true, '')))[1]::text
    ELSE 'coluna ja removida' END FROM tem
  UNION ALL SELECT 115, 'INFO classificacao', 'INFO',
    CASE WHEN tem.pc_cultura THEN (xpath('/row/n/text()', query_to_xml(
      'select coalesce(string_agg(x, ''; ''), ''nenhum parametro'') as n from (select c.cultura || '': '' || count(*) || '' parametros'' as x from tblparametroclassificacao p join tblcultura c using (codcultura) group by c.cultura order by c.cultura) s', false, true, '')))[1]::text
    WHEN to_regclass('tbltabelaclassificacaoitem') IS NOT NULL THEN 'modelo de julho (tabelas): o 25 migra os valores de cada tabela padrao'
    ELSE 'sem classificacao' END FROM tem
)
SELECT passo, status, detalhe
  FROM (SELECT * FROM passo UNION ALL SELECT * FROM conta) x
 ORDER BY ordem;

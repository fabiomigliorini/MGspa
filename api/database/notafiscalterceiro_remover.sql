-- Remocao total do dominio Mg\NotaFiscalTerceiro.
--
-- Foi uma tentativa de substituir o Mg\NfeTerceiro que nunca vingou. Parou de escrever em
-- 2021-07-16 17:30 e desde entao nem uma linha foi alterada — criacao e alteracao mais
-- recentes sao o mesmo instante, em todas as 6 tabelas. O NfeTerceiro e o vivo e recebe
-- nota todo dia. As 12 classes PHP nao tinham rota nem chamador; o unico ponto de contato
-- com codigo vivo era tbldistribuicaodfe.codnotafiscalterceiro.
--
-- Nao ha sobreposicao entre os dois mundos: das 289.940 linhas de tbldistribuicaodfe,
-- 94.307 tem codnfeterceiro, 7.219 tem codnotafiscalterceiro, e ZERO tem as duas.
--
-- ORDEM OBRIGATORIA — as duas secoes NAO rodam juntas:
--
--   secao 1  ->  ANTES do deploy do codigo (so UPDATE, compativel com o codigo velho)
--   deploy   ->  api + fronts, e observar ~1h (2 ciclos do nfe-php:dist-dfe)
--   secao 2  ->  DEPOIS do deploy validado
--
-- O motivo da ordem: o DistribuicaoDfeResource chama NotaFiscalTerceiro() incondicionalmente,
-- uma vez por linha da pagina. Dropar as tabelas antes de subir o codigo derruba com
-- SQLSTATE 42P01 toda a tela de Distribuicao DFe. Na ordem certa, enquanto as tabelas
-- existirem o rollback e um git revert; depois do drop, seria restore de pg_dump.
--
-- BACKUP antes de comecar (10 MB, 43,5 mil linhas irrecuperaveis):
--
--   docker exec mgdb-mgdb-1 pg_dump -U mgsis -d mgsis --no-owner \
--     -t 'mgsis.tblnotafiscalterceiro*' > backup_notafiscalterceiro_$(date +%F).sql
--
--   docker exec mgdb-mgdb-1 psql -U mgsis -d mgsis -Atc \
--     "copy (select coddistribuicaodfe, codnotafiscalterceiro from mgsis.tbldistribuicaodfe
--             where codnotafiscalterceiro is not null) to stdout csv header" \
--     > backup_distribuicaodfe_nft_$(date +%F).csv
--
-- O pg_dump pega as 6 tabelas e as 7 sequences. O CSV pega o mapeamento
-- coddistribuicaodfe -> codnotafiscalterceiro, que morre junto com a coluna e e a unica
-- informacao aqui que nao da para reconstruir de outro jeito.


-- ============================================================================
-- SECAO 1 — BACKFILL. Rodar ANTES do deploy do codigo.
-- ============================================================================
--
-- Os dois updates sao ADITIVOS: so preenchem onde esta nulo, nunca sobrescrevem.
-- SQL puro de proposito, sem Eloquent, para nao carimbar alteracao/codusuarioalteracao —
-- a informacao e de 2021 e nao foi ninguem que a alterou agora.
--
-- Nao existe indice em nfechave em nenhuma das duas tabelas: vai ser seq scan + hash join
-- (290k x 94k), poucos segundos, mas trava alguns milhares de linhas. Rodar fora do pico.
-- Conferido que nao ha nfechave duplicada em nenhum dos dois lados, entao o "update ... from"
-- e deterministico (com chave repetida o Postgres escolheria uma linha arbitrariamente).

-- 1a) Liga as DFe legadas a NFe viva equivalente, pela chave de acesso.
--
-- Sem isto, as 7.219 linhas que hoje exibem emitente/valor na tela de Distribuicao DFe
-- ficariam em branco depois que o bloco do Resource passar a ler NfeTerceiro.

update mgsis.tbldistribuicaodfe d
   set codnfeterceiro = n.codnfeterceiro
  from mgsis.tblnfeterceiro n
 where n.nfechave = d.nfechave
   and d.codnotafiscalterceiro is not null
   and d.codnfeterceiro is null;

-- Esperado ~7137 (das 7.219; as ~82 restantes nao tem NFe correspondente).
-- CONFERIR o numero antes de seguir:
--
--   select count(*) total_legado, count(n.codnfeterceiro) casam_por_chave
--     from mgsis.tbldistribuicaodfe d
--     left join mgsis.tblnfeterceiro n on n.nfechave = d.nfechave
--    where d.codnotafiscalterceiro is not null;


-- 1b) Preserva a natureza da operacao, que so existe do lado morto.
--
-- O campo natureza do tblnfeterceiro so passou a ser preenchido de forma consistente
-- depois: 57% no historico todo, 98,8% no ultimo ano, mas apenas 1,5% nas notas de
-- 2020-2021. Sem esta copia, ~1.650 DFe daquela epoca perderiam a natureza exibida na
-- tela — o unico campo que o repontamento pioraria. Sao 878 NFes, relacao 1:1.

update mgsis.tblnfeterceiro n
   set natureza = o.natop
  from mgsis.tblnotafiscalterceiro o
 where o.nfechave = n.nfechave
   and n.natureza is null
   and o.natop is not null;

-- Esperado 878. A conferencia abaixo mede o efeito real na tela e deve sair 109 ANTES
-- e 1753 DEPOIS:
--
--   select count(n.natureza)
--     from mgsis.tbldistribuicaodfe d
--     join mgsis.tblnotafiscalterceiro o on o.codnotafiscalterceiro = d.codnotafiscalterceiro
--     join mgsis.tblnfeterceiro n on n.nfechave = d.nfechave
--    where d.codnotafiscalterceiro is not null;


-- ============================================================================
-- SECAO 2 — DROP. Rodar DEPOIS do deploy validado.
-- ============================================================================
--
-- Tudo numa transacao so porque DDL no Postgres e transacional: se um drop falhar, o
-- drop column do comeco volta atras junto, e nao fica meio dominio removido.
--
-- Sem CASCADE de proposito. RESTRICT e o default e e o que queremos: o inventario de
-- dependencias (nenhuma view, matview, trigger, function, rule ou check) foi levantado no
-- banco de DEV. Se em producao existir algo que escapou, o comando TEM que falhar, em vez
-- de dropar em silencio o que ninguem mapeou.
--
-- O lock_timeout importa: o drop column precisa de ACCESS EXCLUSIVE em tbldistribuicaodfe
-- e entra na fila atras de qualquer leitura longa — sem timeout ele bloqueia todo mundo
-- enquanto espera. Se estourar, repetir entre ciclos do nfe-php:dist-dfe (a cada 30 min).

begin;
set local lock_timeout = '5s';

-- 1) Corta a unica amarra externa. Leva junto a FK fk_tbldistribuicaodfe_tblnotafiscalterceiro
--    e o indice idx_fk_tbldistribuicaodfe_tblnotafiscalterceiro. E metadata-only, mesmo com
--    289.940 linhas na tabela. A rastreabilidade das 7.219 fica preservada por nfechave,
--    preenchida em 289.554 das 289.940 linhas.
alter table mgsis.tbldistribuicaodfe drop column codnotafiscalterceiro;

-- 2) Folhas primeiro, raiz por ultimo, seguindo as FKs internas: item e produtobarra
--    apontam para grupo; grupo, duplicata e pagamento apontam para a raiz.
drop table mgsis.tblnotafiscalterceiroprodutobarra;
drop table mgsis.tblnotafiscalterceiroitem;
drop table mgsis.tblnotafiscalterceirogrupo;
drop table mgsis.tblnotafiscalterceiroduplicata;
drop table mgsis.tblnotafiscalterceiropagamento;
drop table mgsis.tblnotafiscalterceiro;

-- 3) Das 7 sequences do dominio, 6 sao OWNED BY e caem junto com as tabelas. Esta aqui
--    nao tem entrada em pg_depend — e usada como default nextval() mas nunca foi vinculada
--    a coluna — entao sobreviveria ao drop table e ficaria orfa no schema.
drop sequence mgsis.tblnotafiscalterceiropagament_codnotafiscalterceiropagament_seq;

commit;

-- Conferencia final. Usa pg_class, e nao information_schema.tables, porque precisa pegar
-- tambem a sequence orfa acima — o \dt nao a mostraria. Tem que dar 0:
--
--   select count(*) from pg_class where relname like '%notafiscalterceiro%';

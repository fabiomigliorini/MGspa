-- Log de toda conversa com a SEFAZ.
--
-- Metadado aqui, request/response gzipado em disco (conversas/{filial}/{amb}/{Y}/{m}/{d}/).
-- Duas finalidades:
--   1. Diagnostico: responder "o que exatamente foi mandado e o que voltou" quando uma
--      emissao falha. Hoje sobra so uma linha no laravel.log.
--   2. Fonte da contingencia automatica: a decisao de entrar/sair de contingencia le as
--      ultimas N conversas desta tabela, em vez de contador volatil no Redis.
--
-- EXCECAO DELIBERADA ao padrao de auditoria do projeto: e tabela append-only, a linha
-- nunca e alterada nem inativada, entao nao existem alteracao/codusuarioalteracao/inativo.
-- codusuariocriacao e nullable porque robo e scheduler nao tem usuario.
--
-- Volume esperado: ~1.200-1.500 linhas/dia (~135 MB/ano). Metadado fica para sempre; o
-- .gz em disco tem retencao de 2 anos (nfe-php:limpar-conversas).

create table tblsefazcomunicacao (
  codsefazcomunicacao bigserial primary key,
  codfilial           bigint not null references tblfilial(codfilial) on update cascade,
  codnotafiscal       bigint references tblnotafiscal(codnotafiscal) on update cascade,
  operacao            varchar(50) not null,
  ambiente            smallint not null,
  tentativa           smallint not null default 1,
  httpcode            integer,
  cstat               varchar(4),
  xmotivo             varchar(255),
  duracaoms           integer not null,
  sucesso             boolean not null default false,
  erro                varchar(500),
  arquivo             varchar(255),
  codusuariocriacao   bigint references tblusuario(codusuario) on update cascade,
  criacao             timestamp(0) not null default now()
);

-- Serve a consulta da contingencia (ultimas N conversas da empresa) e a listagem por filial
create index idx_tblsefazcomunicacao_filial_criacao
  on tblsefazcomunicacao (codfilial, criacao desc, codsefazcomunicacao desc);

-- Serve o card "Comunicacao com a SEFAZ" na tela da nota
create index idx_tblsefazcomunicacao_codnotafiscal
  on tblsefazcomunicacao (codnotafiscal);

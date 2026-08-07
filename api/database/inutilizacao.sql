-- Inutilizacao de numeracao por FAIXA.
--
-- O sefazInutiliza($serie, $nNFIni, $nNFFin, ...) sempre cobriu uma faixa — a propria
-- rejeicao 256 diz "uma NF-e DA FAIXA ja esta inutilizada". Mas o sistema passava
-- $nf->numero nos dois parametros (faixa de um) e, pior, para inutilizar uma lacuna o
-- NotaFiscalLacunaService CRIAVA uma tblnotafiscal falsa so para carregar o numero,
-- copiando a emissao da nota anterior.
--
-- Com esta tabela a inutilizacao passa a ser o que sempre foi fiscalmente: um ato sobre
-- um INTERVALO de numeracao, que nao pertence a documento nenhum.

create table tblinutilizacao (
  codinutilizacao     bigserial primary key,
  codfilial           bigint not null references tblfilial(codfilial) on update cascade,
  modelo              smallint not null,
  serie               integer not null,
  numeroinicial       integer not null,
  numerofinal         integer not null,
  ambiente            smallint not null,
  justificativa       varchar(255) not null,
  protocolo           varchar(100),
  protocolodata       timestamp(0),
  cstat               varchar(4),
  xmotivo             varchar(255),
  arquivo             varchar(255),
  criacao             timestamp(0) default now(),
  codusuariocriacao   bigint references tblusuario(codusuario) on update cascade,
  alteracao           timestamp(0) default now(),
  codusuarioalteracao bigint references tblusuario(codusuario) on update cascade,
  constraint chk_tblinutilizacao_faixa check (numerofinal >= numeroinicial)
);

create index idx_tblinutilizacao_faixa
  on tblinutilizacao (codfilial, modelo, serie, numeroinicial, numerofinal);

create index idx_tblinutilizacao_codusuariocriacao_fkey on tblinutilizacao (codusuariocriacao);
create index idx_tblinutilizacao_codusuarioalteracao_fkey on tblinutilizacao (codusuarioalteracao);

-- BACKFILL — obrigatorio, nao opcional.
--
-- As inutilizacoes existentes viram faixas de 1. Sem isso o detectarLacunas voltaria a
-- oferecer como lacuna todo numero ja inutilizado no passado, e alguem poderia
-- reinutilizar por cima. Conferir depois de rodar:
--
--   select (select count(*) from tblinutilizacao) backfill,
--          (select count(*) from tblnotafiscal where nfeinutilizacao is not null) origem;
--
-- Os dois numeros TEM que bater.
--
-- RESSALVA CONHECIDA: a coluna tblnotafiscal.nfeinutilizacao serve a DUAS coisas — alem da
-- inutilizacao de numeracao, o NFePHPService::vincularProtocoloDenegacao() grava ali o
-- protocolo de DENEGACAO. O backfill nao consegue separar os dois casos retroativamente,
-- entao algumas linhas serao de notas denegadas, nao de numeracao inutilizada.
--
-- Na pratica isso e inocuo: o detectarLacunas so olha numeros SEM nota, e nota denegada
-- tem nota; e a validacao de sobreposicao barrar aquele numero esta correta, porque o
-- numero foi de fato usado. Fica registrado para quem estranhar a listagem depois.

insert into tblinutilizacao (codfilial, modelo, serie, numeroinicial, numerofinal, ambiente,
                             justificativa, protocolo, protocolodata, criacao, codusuariocriacao)
select nf.codfilial,
       nf.modelo,
       nf.serie,
       nf.numero,
       nf.numero,
       f.nfeambiente,
       coalesce(nullif(trim(nf.justificativa), ''), 'Inutilizacao importada do historico'),
       nf.nfeinutilizacao,
       nf.nfedatainutilizacao,
       nf.criacao,
       nf.codusuariocriacao
from tblnotafiscal nf
inner join tblfilial f on (f.codfilial = nf.codfilial)
where nf.nfeinutilizacao is not null;

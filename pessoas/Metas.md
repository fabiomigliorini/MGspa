# 📘 Sistema de Bonificação — Blueprint Arquitetural

## 1. Visão Geral

O Sistema de Bonificação é um motor declarativo e reprocessável que calcula valores variáveis pagos aos colaboradores com base em:

- Vendas realizadas
- Metas individuais e por unidade
- Ranking
- Regras específicas do período
- Valores fixos concedidos pela gestão

Ele funciona baseado em:

- Períodos fechados (tblmeta)
- Unidades operacionais (tblunidadenegocio)
- Participação declarativa por período
- Ledger financeiro imutável por meta fechada

---

# 2. Conceitos Fundamentais

## 2.1 Unidade de Negócio

Representa a entidade operacional que possui meta própria.

Exemplos:

- Botânico
- Centro
- Imperial
- Andre Maggi
- Sinopel (remoto)
- Administrativo
- Fazenda
- Depósito

Não confundir com `tblfilial` (que é fiscal).

---

## 2.2 Meta (Período)

Tabela: `tblmeta`

Define o período de apuração.

```text
periodoinicial
periodofinal
status (A/B/F)
```

Status possíveis:

| Status | Significado                                                    |
| ------ | -------------------------------------------------------------- |
| A      | Aberta (processamento ativo)                                   |
| B      | Bloqueada (não recebe novos lançamentos, mas pode reprocessar) |
| F      | Fechada (imutável)                                             |

Regra:

- Apenas uma meta pode estar com status = 'A'
- Reprocessamento permitido apenas em A ou B

---

## 2.3 Ledger de Bonificação

Tabela: `tblbonificacaoevento`

É o extrato oficial e único de valores.

Cada linha representa um evento financeiro:

- Bonificação de venda
- Bonificação de caixa
- Bonificação de subgerente
- Bonificação de meta
- Prêmio de ranking
- Valor fixo (limpeza, gestão, etc.)
- Evento negativo (devolução)

Após fechamento da meta, os registros tornam-se imutáveis.

---

# 3. Estrutura de Dados

## 3.1 tblunidadenegocio

```text
codunidadenegocio
descricao
codfilial (nullable)
inativo
criacao
codusuariocriacao
alteracao
codusuarioalteracao
```

---

## 3.2 tblmeta

```text
codmeta
periodoinicial
periodofinal
status (A/B/F)
criacao
codusuariocriacao
alteracao
codusuarioalteracao
```

---

## 3.3 tblmetaunidadenegocio

Meta definida para cada unidade no período.

```text
codmetaunidadenegocio
codmeta
codunidadenegocio
valormeta
criacao
codusuariocriacao
alteracao
codusuarioalteracao
```

---

## 3.4 tblmetaunidadenegociopessoa

Fotografia declarativa da participação da pessoa naquele período.

```text
codmetaunidadenegociopessoa
codmeta
codunidadenegocio
codpessoa

percentualvenda
percentualcaixa
percentualsubgerente
percentualremoto
percentualxerox

valorlimpeza

inativo

criacao
codusuariocriacao
alteracao
codusuarioalteracao
```

Regras:

- Se percentual = NULL → não participa daquela modalidade
- Pessoa pode acumular funções
- Se não existir registro, sistema cria automaticamente com percentual padrão

---

## 3.5 tblbonificacaoevento

```text
codbonificacaoevento
codmeta
codunidadenegocio
codpessoa
codnegocio (nullable)
descricao
valor
manual boolean
criacao
codusuariocriacao
alteracao
codusuarioalteracao
```

Regras:

- Eventos SISTEMA → manual = false
- Eventos lançados pela gestão → manual = true
- Reprocessamento apaga apenas eventos manual = false

---

# 4. Regras de Cálculo

## 4.1 Venda Normal

Condições:

- tblnegocio.codnegociostatus != 3 (cancelada)
- Dentro do período da meta A ou B

Processamento:

1. Identifica unidade:

   - Se tblpdv.alocacao = 'R' → Sinopel
   - Senão → unidade vinculada à filial

2. Se produto possui `bonificacaoxerox = true`:

   - Gera evento percentualxerox

3. Gera:

   - Evento percentualvenda (vendedor)
   - Evento percentualcaixa (se PDV não remoto)
   - Evento percentualsubgerente

---

## 4.2 Venda Cancelada

- Status = 3
- Ignorada no processamento
- No reprocessamento, eventos anteriores são apagados

---

## 4.3 Devolução

Identificada por:

- tblnaturezaoperacao.vendadevolucao = true

Processamento:

- Gera evento negativo na meta aberta
- Não altera meta fechada

---

## 4.4 Meta Individual

No fechamento ou reprocessamento:

Se vendas >= meta:

- Gera evento adicional (ex: +0,25%)

---

## 4.5 Ranking

- Calculado dinamicamente no dashboard
- Exclui Xerox
- Evento de prêmio gerado apenas no fechamento

---

## 4.6 Meta Loja

- Inclui vendas Xerox
- Exclui Sinopel
- Subgerente recebe percentual sobre total

---

## 4.7 Caixa

- Recebe percentual se PDV não for remoto
- Se PDV for remoto → ignora

---

## 4.8 Valores Fixos

Podem existir eventos manuais:

- Limpeza
- Bônus gestão
- Meta remoto fixa
- Administrativo

Registrados diretamente em `tblbonificacaoevento` com manual = true

---

# 5. Reprocessamento

Permitido se meta.status in ('A','B')

Fluxo:

1. Lock da meta
2. Delete eventos manual = false
3. Processa vendas
4. Processa metas
5. Calcula ranking
6. Insere prêmios
7. Unlock

Nunca permitido se status = 'F'.

---

# 6. Transição de Período

Fluxo mensal:

1. Meta atual (A) → B
2. Criar nova meta (A)
3. Duplicar configuração de pessoas
4. Após conferência → antiga vira F

---

# 7. Dashboard do Colaborador

Deve mostrar:

- Total provisório
- Ranking atual
- Progresso contra meta
- Meta diária (linear inicialmente)
- Extrato detalhado (ledger)

Sempre indicar:

> Valores sujeitos a alteração até fechamento.

---

# 8. Métrica Estratégica

Sistema permitirá calcular:

```text
Custo variável (%) =
SUM(tblbonificacaoevento.valor)
/ SUM(vendas líquidas da unidade)
```

Por meta.

---

# 9. Garantias do Modelo

- Imutabilidade pós-fechamento
- Auditoria completa
- Reprocessamento controlado
- Sem retroatividade indevida
- Multifunção suportada
- Unidade operacional isolada

---

# 📘 Blueprint Backend Completo

# Domínio `Mg\Meta`

---

# 1️⃣ Estrutura Final do Domínio

```
app/
└── Mg/
    └── Meta/
        ├── Meta.php
        ├── MetaUnidadeNegocio.php
        ├── MetaUnidadeNegocioPessoa.php
        ├── BonificacaoEvento.php
        ├── UnidadeNegocio.php
        │
        ├── Services/
        │   ├── MetaService.php
        │   ├── BonificacaoService.php
        │   ├── ReprocessamentoMetaService.php
        │   ├── RankingService.php
        │   └── MetaProjectionService.php
        │
        ├── Jobs/
        │   └── ProcessaBonificacaoNegocioJob.php
        │
        ├── Commands/
        │   ├── ReprocessaMetaCommand.php
        │   ├── FinalizaMetaCommand.php
        │   └── CriarNovaMetaCommand.php
        │
        └── Actions/
            └── DuplicarConfiguracaoMetaAction.php
```

---

# 2️⃣ Entidades do Domínio

---

## 🔹 Meta (tblmeta)

Responsável por:

- Período
- Status (A/B/F)
- Processamento
- Percentuais padrão

Estados:

| Status | Significado |
| ------ | ----------- |
| A      | Aberta      |
| B      | Bloqueada   |
| F      | Fechada     |

---

## 🔹 MetaUnidadeNegocio

Define:

- Meta da unidade
- Meta vendedor
- Meta caixa
- Meta xerox

---

## 🔹 MetaUnidadeNegocioPessoa

Define:

- Participação no período
- Percentuais individuais
- Valores fixos concedidos
- Substituições manuais

Sem controle de datas.

Se existe registro → participa.

---

## 🔹 BonificacaoEvento (Ledger)

Cada linha representa:

- Venda
- Devolução
- Meta atingida
- Prêmio ranking
- Bônus fixo
- Ajuste manual

Nunca altera.
Só insere ou apaga (quando reprocessa e manual = false).

---

# 3️⃣ Fluxo Completo do Sistema

---

# 🔁 3.1 Venda Acontece

1. Venda é salva.
2. Event dispara:

```php
ProcessaBonificacaoNegocioJob::dispatch($codnegocio);
```

Sempre fila.

---

# 🔹 ProcessaBonificacaoNegocioJob

```php
handle()
{
    $meta = MetaService::metaAberta();

    if (!$meta || $meta->status !== 'A') {
        return;
    }

    BonificacaoService::processarNegocio($codnegocio, $meta);
}
```

Sem transaction.

---

# 🔹 BonificacaoService::processarNegocio()

Fluxo:

```pseudo
1. Buscar negócio
2. Se status = 3 → return
3. Identificar unidade:
   - PDV R → Sinopel
   - Senão → Unidade da filial
4. Processar VENDA VENDEDOR
5. Processar VENDA CAIXA
6. Processar VENDA SUBGERENTE
7. Processar XEROX por item
8. Inserir eventos
```

Se registro da pessoa não existir:
→ criar automaticamente com padrão.

---

# 🔁 3.2 Reprocessamento Manual

Entry Point:

```
ReprocessaMetaCommand
```

---

## ReprocessaMetaCommand

```php
execute($codmeta)
{
    DB::transaction(function () {

        $meta = Meta::lockForUpdate()->findOrFail($codmeta);

        validar status A ou B

        meta->processando = true

        ReprocessamentoMetaService::reprocessar($meta);

        meta->processando = false
    });
}
```

---

## ReprocessamentoMetaService

Sem transação.

```pseudo
1. delete eventos manual = false
2. buscar negócios do período
3. foreach:
      BonificacaoService::processarNegocio(...)
4. RankingService::recalcular(...)
5. MetaProjectionService::recalcularMetas(...)
```

---

# 🔁 3.3 Fechamento de Meta

Entry Point:

```
FinalizaMetaCommand
```

---

## FinalizaMetaCommand

```php
DB::transaction(function () {

    meta.status deve ser B

    ReprocessaMetaCommand

    gerar eventos finais:
        - prêmio ranking
        - bônus meta remoto
        - bônus fixo setor

    meta.status = 'F'

    CriarNovaMetaCommand
});
```

---

# 4️⃣ Cálculo de Ranking

Não armazenado por venda.

Calculado por agregação:

```sql
sum(valor)
group by codpessoa
order by total desc
```

Xerox excluído do ranking.

Evento de prêmio:
→ lançado apenas no fechamento.

---

# 5️⃣ Meta Projection (Dashboard)

MetaProjectionService calcula:

- Venda acumulada
- Meta proporcional
- Diferença
- Projeção linear (inicialmente)

Distribuição inicial:
→ Linear por dias úteis.

Depois pode evoluir para histórico ponderado.

---

# 6️⃣ Regras Especiais Implementadas

---

## Cancelamento

```php
codnegociostatus == 3 → ignora
```

---

## Devolução

Se vendadevolucao:

```php
valor = valor * -1
```

Entra na meta aberta.

---

## Caixa do Xerox

Se:

```php
pdv.alocacao == 'X'
```

→ não calcula comissão de caixa.

---

## Sinopel

Se:

```php
pdv.alocacao == 'R'
```

→ unidade Sinopel
→ não entra meta loja física

---

## Meta Fechada

Se status = 'F':
→ nenhum processamento permitido
→ reprocessamento bloqueado

---

# 7️⃣ Controle de Concorrência

Meta possui:

```sql
processando boolean default false
```

Reprocessamento exige:

- Lock pessimista
- Status A ou B
- processando = false

---

# 8️⃣ Garantias Arquiteturais

✔ Ledger imutável
✔ Reprocessamento determinístico
✔ Meta fechada nunca alterada
✔ Venda sempre processada via fila
✔ Services sem dependência de transação
✔ Commands controlam consistência

---

# 9️⃣ Performance

~40k negócios/mês

Reprocessamento:

- delete eventos
- loop linear
- index adequado

Seguro.

---

# 🔟 Fluxo Mensal Final

```
Dia 25:
meta.status = B

Dia 26:
nova meta criada automaticamente

Após conferência:
FinalizaMetaCommand(meta anterior)
```

# Conversao do Banco

```
alter table tblmeta
    add column status char(1) not null default 'A',
    add column processando boolean not null default false;

create index idx_tblmeta_status on tblmeta(status);

update tblmeta
set status = 'F'
where codmeta not in (
    select codmeta
    from tblmeta
    order by periodofinal desc
    limit 1
);

update tblmeta
set status = 'A'
where codmeta in (
    select codmeta
    from tblmeta
    order by periodofinal desc
    limit 1
);


create unique index uq_tblmeta_aberta
on tblmeta (status)
where status = 'A';


alter table tblproduto
    add column bonificacaoxerox boolean not null default false;

update tblproduto
set bonificacaoxerox = true,
    alteracao = now()
where codsubgrupoproduto = 2951;


alter table tblpdv
    add column alocacao char(1) not null default 'C';

update tblpdv pdv
set alocacao = 'R',
    alteracao = now()
from (
    select codpdv
    from tblnegocio
    group by codpdv
    having
        sum(case when codpessoavendedor = 10000051 then 1 else 0 end)::numeric
        / count(*) >= 0.8
) sinopel
where pdv.codpdv = sinopel.codpdv;

update tblpdv pdv
set alocacao = 'X',
    alteracao = now()
from (
    select n.codpdv
    from tblnegocioprodutobarra npb
    join tblnegocio n on n.codnegocio = npb.codnegocio
    join tblprodutobarra pb on pb.codprodutobarra = npb.codprodutobarra
    join tblproduto p on p.codproduto = pb.codproduto
    group by n.codpdv
    having
        sum(case when p.bonificacaoxerox then 1 else 0 end)::numeric
        / count(*) >= 0.8
) xerox
where pdv.codpdv = xerox.codpdv
and pdv.alocacao <> 'R';

update tblpdv
set alocacao = 'C',
    alteracao = now()
where alocacao not in ('R','X');


alter table tblmetafilialpessoa
    add column percentualvenda numeric(6,3),
    add column percentualcaixa numeric(6,3),
    add column percentualsubgerente numeric(6,3),
    add column percentualxerox numeric(6,3),
    add column valorfixo numeric(14,2),
    add column descricaovalorfixo varchar(200);


create table tblunidadenegocio (
    codunidadenegocio bigserial primary key,
    descricao varchar(100) not null,
    codfilial bigint,
    inativo timestamp(0),
    criacao timestamp(0) not null default now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) not null default now(),
    codusuarioalteracao bigint
);


create table tblmetaunidadenegocio (
    codmetaunidadenegocio bigserial primary key,
    codmeta bigint not null,
    codunidadenegocio bigint not null,
    valormeta numeric(14,2),
    valormetavendedor numeric(14,2),
    valormetacaixa numeric(14,2),
    valormetaxerox numeric(14,2),
    criacao timestamp(0) not null default now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) not null default now(),
    codusuarioalteracao bigint
);

create index idx_tblmetaunidadenegocio_meta
on tblmetaunidadenegocio(codmeta);


create table tblmetaunidadenegociopessoa (
    codmetaunidadenegociopessoa bigserial primary key,
    codmeta bigint not null,
    codunidadenegocio bigint not null,
    codpessoa bigint not null,
    percentualvenda numeric(6,3),
    percentualcaixa numeric(6,3),
    percentualsubgerente numeric(6,3),
    percentualxerox numeric(6,3),
    valorfixo numeric(14,2),
    descricaovalorfixo varchar(200),
    criacao timestamp(0) not null default now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) not null default now(),
    codusuarioalteracao bigint
);

create index idx_tblmetaunp_meta
on tblmetaunidadenegociopessoa(codmeta);

create index idx_tblmetaunp_pessoa
on tblmetaunidadenegociopessoa(codpessoa);


create table tblbonificacaoevento (
    codbonificacaoevento bigserial primary key,
    codmeta bigint not null,
    codunidadenegocio bigint not null,
    codpessoa bigint not null,
    codnegocio bigint,
    codnegocioprodutobarra bigint,
    tipo varchar(50) not null,
    descricao varchar(200),
    valor numeric(14,2) not null,
    manual boolean not null default false,
    criacao timestamp(0) not null default now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) not null default now(),
    codusuarioalteracao bigint
);

create index idx_tblbonificacaoevento_meta
on tblbonificacaoevento(codmeta);

create index idx_tblbonificacaoevento_pessoa
on tblbonificacaoevento(codpessoa);

alter table tblcargo
    alter column comissaocaixa type numeric(6,3);

create index idx_tblnegocio_lancamento
on tblnegocio(lancamento);

comment on table tblmeta is
'Define o período de apuração da bonificação. Apenas uma meta pode estar com status = A (Aberta).';

comment on column tblmeta.status is
'A = Aberta (recebe lançamentos automaticamente)
B = Bloqueada (não recebe novos lançamentos, mas pode ser reprocessada)
F = Fechada (imutável, não permite reprocessamento)';

comment on column tblmeta.processando is
'Indica que a meta está em processo de reprocessamento. Evita concorrência.';

comment on column tblmeta.periodoinicial is
'Data inicial do período de apuração da bonificação.';

comment on column tblmeta.periodofinal is
'Data final do período de apuração da bonificação.';

comment on table tblunidadenegocio is
'Representa unidade operacional para fins de meta e bonificação. Pode ou não estar vinculada a uma filial fiscal.';

comment on column tblunidadenegocio.codfilial is
'Filial fiscal associada à unidade, quando aplicável.';

comment on column tblpdv.alocacao is
'C = Unidade física da filial
X = PDV exclusivo do setor Xerox
R = Unidade remota (Sinopel)';

comment on column tblproduto.bonificacaoxerox is
'Indica se o produto pertence ao setor Xerox para cálculo de bonificação específica.';

comment on table tblmetaunidadenegocio is
'Define metas financeiras por unidade de negócio dentro do período da meta.';

comment on table tblmetaunidadenegociopessoa is
'Fotografia da participação do colaborador na meta e unidade no período. Percentual NULL indica que não participa daquela modalidade.';

comment on column tblmetaunidadenegociopessoa.percentualvenda is
'Percentual aplicado sobre vendas do colaborador.';

comment on column tblmetaunidadenegociopessoa.percentualcaixa is
'Percentual aplicado sobre vendas passadas no caixa do colaborador.';

comment on column tblmetaunidadenegociopessoa.percentualsubgerente is
'Percentual aplicado sobre total da unidade (inclui Xerox).';

comment on column tblmetaunidadenegociopessoa.percentualxerox is
'Percentual aplicado sobre itens marcados como bonificacaoxerox.';

comment on table tblbonificacaoevento is
'Ledger oficial de bonificação. Cada linha representa um evento financeiro individual.';

comment on column tblbonificacaoevento.manual is
'TRUE = evento lançado manualmente pela gestão.
FALSE = evento gerado automaticamente pelo sistema.';

alter table tblunidadenegocio
    add constraint fk_unidade_filial
    foreign key (codfilial)
    references tblfilial(codfilial);

alter table tblmetaunidadenegocio
    add constraint fk_metaun_meta
    foreign key (codmeta)
    references tblmeta(codmeta);

alter table tblmetaunidadenegocio
    add constraint fk_metaun_unidade
    foreign key (codunidadenegocio)
    references tblunidadenegocio(codunidadenegocio);

alter table tblmetaunidadenegociopessoa
    add constraint fk_metaunp_meta
    foreign key (codmeta)
    references tblmeta(codmeta);

alter table tblmetaunidadenegociopessoa
    add constraint fk_metaunp_unidade
    foreign key (codunidadenegocio)
    references tblunidadenegocio(codunidadenegocio);

alter table tblmetaunidadenegociopessoa
    add constraint fk_metaunp_pessoa
    foreign key (codpessoa)
    references tblpessoa(codpessoa);

alter table tblbonificacaoevento
    add constraint fk_bon_meta
    foreign key (codmeta)
    references tblmeta(codmeta);

alter table tblbonificacaoevento
    add constraint fk_bon_unidade
    foreign key (codunidadenegocio)
    references tblunidadenegocio(codunidadenegocio);

alter table tblbonificacaoevento
    add constraint fk_bon_pessoa
    foreign key (codpessoa)
    references tblpessoa(codpessoa);

alter table tblbonificacaoevento
    add constraint fk_bon_negocio
    foreign key (codnegocio)
    references tblnegocio(codnegocio);

alter table tblbonificacaoevento
    add constraint fk_bon_negprod
    foreign key (codnegocioprodutobarra)
    references tblnegocioprodutobarra(codnegocioprodutobarra);

create index idx_tblbonificacaoevento_meta_pessoa
on tblbonificacaoevento (codmeta, codpessoa);

create unique index uq_tblmetaunp
on tblmetaunidadenegociopessoa (codmeta, codunidadenegocio, codpessoa);

create unique index uq_tblmetaun
on tblmetaunidadenegocio (codmeta, codunidadenegocio);

create index idx_tblmetaun_unidade
on tblmetaunidadenegocio (codunidadenegocio);

create index idx_tblmetaunp_meta_unidade
on tblmetaunidadenegociopessoa (codmeta, codunidadenegocio);

drop materialized view mwvendas;

alter table tblproduto
    drop column comissao;

comment on column tblmeta.percentualcomissaovendedor
is 'LEGADO - Usado apenas pelas telas antigas. Não utilizado pelo novo motor de bonificação.';

comment on column tblmeta.percentualcomissaovendedormeta
is 'LEGADO - Compatibilidade temporária.';

comment on column tblmeta.percentualcomissaosubgerentemeta
is 'LEGADO - Compatibilidade temporária.';

comment on column tblmeta.percentualcomissaoxerox
is 'LEGADO - Compatibilidade temporária.';


```

---
id: TASK-7
title: Aposentar as Metas antigas preservando o historico
status: To Do
assignee: []
created_date: '2026-09-12 15:53'
updated_date: '2026-09-12 17:14'
labels:
  - pessoas
  - api
dependencies: []
priority: medium
type: chore
ordinal: 8000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: pessoas/todo — "Eliminar todas as tabelas, models, controllers, telas da refatoracao de Metas."

Hoje existem DUAS telas de meta vivas no app pessoas:

- ANTIGA: menu "Metas" -> /meta (pages/meta/Index.vue, MetaDashboard.vue, MetaColaboradorDashboard.vue, MetaUnidadeDashboard.vue), backend app/Mg/Meta/.
- NOVA: menu "Metas RH" -> /rh e "Meu Painel" -> /rh/meu-painel, backend app/Mg/Rh/ (Periodo, PeriodoColaborador, Indicador, IndicadorLancamento, Rubrica).

A antiga deve sair, mas o HISTORICO NAO PODE SER PERDIDO. A ideia e recriar os
registros dentro da estrutura nova (tblperiodo / tblperiodocolaborador /
tblindicador / tblindicadorlancamento) antes de derrubar as tabelas velhas.

Situacao dos dados (contagem em 2026-09-12):

- tblmeta: 112, tblmetafilial: 386, tblmetafilialpessoa: 2812 -> ESTE e o historico a migrar.
- tblmetaunidadenegocio, tblmetaunidadenegociopessoa, tblmetaunidadenegociopessoafixo,
  tblmetavendedor, tblbonificacaoevento: 0 registros -> podem ser dropadas direto.

Codigo a remover depois da migracao:

- Backend: app/Mg/Meta/ inteiro (Meta, MetaFilial, MetaFilialPessoa, MetaUnidadeNegocio*,
  MetaVendedor, BonificacaoEvento, MetaController, MetaDashboardController, MetaService,
  MetaResource, MetaListagemResource, Services/{Bonificacao,MetaAggregate,MetaProjecao,MetaReconstrucao}Service).
- Requests: app/Http/Requests/Mg/Meta/{CriarMetaRequest,AtualizarMetaRequest}.php.
- Commands/agendamentos: CriarNovaMetaCommand, FinalizaMetaCommand, ReprocessaMetaCommand
  (conferir o schedule em app/Console/Kernel.php e a fila).
- Rotas: bloco de meta em routes/api.php.
- Relacoes residuais nos models de outros dominios: Filial, UnidadeNegocio, Pessoa,
  Negocio, NegocioProdutoBarra, Cargo.
- Frontend: pages/meta/, rotas /meta em src/router/routes.js, item "Metas" do menu e o
  que sobrar de store/componentes so dela.

Fazer em ordem: (1) mapear de-para dos campos, (2) migrar/recriar o historico e conferir,
(3) so entao remover codigo e dropar tabelas.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 De-para documentado entre as tabelas antigas (tblmeta/tblmetafilial/tblmetafilialpessoa) e as novas (tblperiodo/tblperiodocolaborador/tblindicador/tblindicadorlancamento)
- [ ] #2 Historico das 112 metas / 386 metas de filial / 2812 metas de pessoa recriado na estrutura nova, com conferencia de totais antes e depois
- [ ] #3 Telas /meta e o item Metas do menu removidos; ninguem perde acesso ao historico, que passa a ser consultado pelas telas de RH
- [ ] #4 Backend app/Mg/Meta, requests, commands, agendamentos e rotas removidos; sem referencia orfa em Filial, UnidadeNegocio, Pessoa, Negocio, NegocioProdutoBarra e Cargo
- [ ] #5 Tabelas antigas dropadas (as vazias direto; as com dados so depois da migracao conferida), com script DDL versionado para rodar em producao
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
A DEFINIR ANTES DE COMECAR — o de-para nao e 1:1.

A estrutura antiga era meta por filial e por pessoa (tblmeta -> tblmetafilial ->
tblmetafilialpessoa). A nova e por rubrica/indicador (tblperiodo ->
tblperiodocolaborador -> tblindicador -> tblindicadorlancamento). Nao ha
equivalencia direta, entao precisa ser decidido como o historico entra:

a) cada meta antiga vira um tblperiodo + um tblindicador sintetico ("Meta legado"),
   com um lancamento por pessoa; ou
b) o historico entra so como lancamento consolidado, sem recriar a hierarquia.

Levantar os campos das duas estruturas e propor o de-para antes de escrever
qualquer script de migracao.
<!-- SECTION:NOTES:END -->

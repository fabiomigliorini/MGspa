---
id: TASK-26
title: Transformar os selects de notas em componentes MgSelect
status: To Do
assignee: []
created_date: '2026-09-12 15:53'
updated_date: '2026-10-10 19:07'
labels:
  - notas
dependencies: []
priority: low
type: feature
ordinal: 10000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: notas/todo.md — bloco 02/02 (estava sob DONE sem 'ok'). Conferido: 15 arquivos ja usam MgSelect*, mas 19 ainda tem q-select cru.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Form de natureza de operação usa MgSelectNaturezaOperacao, MgSelectTipoTitulo, MgSelectContaContabil e MgSelectEstoqueMovimentoTipo
- [ ] #2 Filtro de DF-e usa MgSelectFilial
- [ ] #3 MDF-e e conjunto de veículos usam um MgSelectVeiculo novo
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Conferido 15/09/2026: sem avanco desde 12/09 — ainda 19 arquivos com <q-select> cru e 15 com MgSelect* em notas/src.

Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): escopo reescrito: dos 19 arquivos com q-select cru, a maioria são listas fixas (CST, modelo, frete, status, tipo de pagamento), que ficam como q-select. Só os que buscam na API viram MgSelect. O TributacaoSimuladorDrawer fica com a TASK-72.
<!-- SECTION:NOTES:END -->

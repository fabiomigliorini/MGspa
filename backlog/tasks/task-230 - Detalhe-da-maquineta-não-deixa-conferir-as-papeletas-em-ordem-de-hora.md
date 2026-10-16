---
id: TASK-230
title: Detalhe da maquineta não deixa conferir as papeletas em ordem de hora
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-16 16:16'
updated_date: '2026-10-16 16:16'
labels:
  - contas
dependencies: []
priority: low
type: feature
ordinal: 241000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
O card Detalhe do período da maquineta (contas, /maquineta/:cod/:lote) lista as vendas na ordem do borderô (modalidade → bandeira → hora). Para conferir com as papeletas, que ficam empilhadas em ordem de hora, o card ganhou duas abas: Por bandeira (como era) e Por hora (lista única cronológica, com a modalidade e a bandeira na linha). Pedido do Fábio em 15/10/2026.

Arquivo: contas/src/components/maquineta/PeriodoLancamentos.vue. O bloco de cancelamento de outro período fica no fim nas duas abas.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Card Detalhe com as abas Por bandeira (ordem atual) e Por hora (cronológica, modalidade e bandeira na linha)
<!-- AC:END -->

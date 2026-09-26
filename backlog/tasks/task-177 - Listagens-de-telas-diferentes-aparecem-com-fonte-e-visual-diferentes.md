---
id: TASK-177
title: Listagens de telas diferentes aparecem com fonte e visual diferentes
status: To Do
assignee: []
created_date: '2026-09-26 17:37'
labels:
  - components
dependencies: []
priority: low
type: chore
ordinal: 191000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: validacao da tela de Vales Emitidos em 26/09/2026. As listagens do projeto misturam q-table e q-list (com q-infinite-scroll), inclusive telas vizinhas do mesmo modulo: Modelos de Vale usa q-table (fonte 13px, do CSS do Quasar) e Vales Emitidos usa q-list (14px, herdado do body). Contagem de paginas em src/pages: contas 13 q-table / 5 q-list, pessoas 7/5, negocios 2/4, notas 2/5, estoque 0/3, agro 1/1.

Objetivo: definir UM padrao de listagem (q-table ou q-list, e quando cada um), documentar (CLAUDE.md) e ajustar as telas. Registro com duas linhas por item e possivel nos dois (q-table: duas linhas na mesma celula, ou duas q-tr por registro no slot #body).
<!-- SECTION:DESCRIPTION:END -->

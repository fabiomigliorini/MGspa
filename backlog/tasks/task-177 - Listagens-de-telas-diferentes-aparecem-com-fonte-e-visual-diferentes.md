---
id: TASK-177
title: Listagens de telas diferentes aparecem com fonte e visual diferentes
status: To Do
assignee: []
created_date: '2026-09-26 17:37'
updated_date: '2026-10-10 18:45'
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

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Drawer de filtros das listagens com o mesmo visual em todos os apps (hoje são duas versões copiadas)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Achado em 10/10/2026 (TASK-207): FilterDrawerShell.vue e FilterGroup.vue estão copiados em agro, contas, estoque e negocios (src/components), em duas variantes. contas/estoque: separador entre os grupos e titulo com q-mb-md; agro/negocios: sem separador (comentado), titulo com q-mt-md q-mb-sm e o shell com no-wrap. Caminho: escolher um visual, mover os dois para @components (prefixo Mg) e trocar os imports (31 arquivos usam).
<!-- SECTION:NOTES:END -->

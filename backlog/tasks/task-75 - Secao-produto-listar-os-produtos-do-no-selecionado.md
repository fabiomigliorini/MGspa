---
id: TASK-75
title: 'Secao-produto: listar os produtos do no selecionado'
status: To Do
assignee: []
created_date: '2026-09-12 15:56'
updated_date: '2026-10-10 19:06'
labels:
  - estoque
dependencies: []
priority: medium
type: feature
ordinal: 78000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: estoque/docs/BLUEPRINT_MIGRACAO_MGLARA.md — Fase A. A arvore em estoque/src/pages/secao-produto/Index.vue ja inativa nos e filtra; falta listar os produtos do no.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 O nó da árvore de seções abre a listagem de produtos já filtrada por ele (filtro na URL)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): a listagem de produtos já filtra por seção/família/grupo/subgrupo no drawer (ProdutoFiltrosDrawer, ProdutoService); falta o caminho: o nó não navega e produto/Index.vue não lê route.query.
<!-- SECTION:NOTES:END -->

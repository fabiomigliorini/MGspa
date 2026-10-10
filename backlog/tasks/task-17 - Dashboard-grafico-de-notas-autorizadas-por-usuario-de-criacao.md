---
id: TASK-17
title: 'Dashboard: grafico de notas autorizadas por usuario de criacao'
status: To Do
assignee: []
created_date: '2026-09-12 15:53'
updated_date: '2026-10-10 19:07'
labels:
  - notas
dependencies: []
priority: low
type: feature
ordinal: 19000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: notas/todo.md — secao "no dashboard"
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Dashboard mostra as notas autorizadas por usuário de criação
- [ ] #2 Dashboard mostra o percentual de canceladas/inutilizadas por usuário de criação (ex-TASK-18)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): absorve a TASK-18. Uma consulta agrupada por codusuariocriacao devolve as duas coisas; o índice idx_tblnotafiscal_codusuariocriacao_fkey já existe.
<!-- SECTION:NOTES:END -->

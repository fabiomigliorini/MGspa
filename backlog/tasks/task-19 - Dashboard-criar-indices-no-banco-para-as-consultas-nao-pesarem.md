---
id: TASK-19
title: 'Dashboard: criar indices no banco para as consultas nao pesarem'
status: Done
assignee: []
created_date: '2026-09-12 15:53'
updated_date: '2026-10-10 19:07'
labels:
  - notas
dependencies: []
priority: medium
type: feature
ordinal: 21000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: notas/todo.md — secao "no dashboard"
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): já estava feito no banco do dev: idx_tblnotafiscal_status (codfilial, modelo, status, saida, codnotafiscal), idx_tblnotafiscal_sort (saida desc, codnotafiscal desc), idx_tblnotafiscal_emissao (where emitida), idx_tblnotafiscal_busca_principal. volumeMensal em 0,84 s (Index Only Scan); porFilial/erroPorFilial em 0,39 s. Não há DDL desses índices no repositório.
<!-- SECTION:NOTES:END -->

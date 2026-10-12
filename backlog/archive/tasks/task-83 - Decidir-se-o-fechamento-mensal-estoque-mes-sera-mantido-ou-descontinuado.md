---
id: TASK-83
title: Decidir se o fechamento mensal (estoque-mes) sera mantido ou descontinuado
status: To Do
assignee: []
created_date: '2026-09-12 15:56'
updated_date: '2026-10-10 19:06'
labels:
  - estoque
dependencies: []
priority: low
type: feature
ordinal: 86000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: estoque/docs/BLUEPRINT_MIGRACAO_MGLARA.md — Fase C.8. O blueprint marca como "decisao antes de codar". Conferido: nao existe nada de estoque-mes no app.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): juntada na TASK-81. O estoque-mes não é fechamento descontinuável: tblestoquemes tem 6,1 milhões de linhas, tblestoquemovimento.codestoquemes é NOT NULL e a api usa (EstoqueMesService, conferência, relatórios, Domínio). A tela do MGLara é o kardex do mês, que é a TASK-81.
<!-- SECTION:NOTES:END -->

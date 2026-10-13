---
id: TASK-47
title: Dar permissao de natureza de operacao por grupo
status: To Do
assignee: []
created_date: '2026-09-12 15:53'
updated_date: '2026-10-10 19:11'
labels:
  - negocios
dependencies: []
priority: low
type: enhancement
ordinal: 113000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: negocios/todo — secao SEGURANCA. "Exemplo Caixa nao consegue fazer Doacao." No arquivo original constava "(Allan)".
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Cada natureza de operação diz quais grupos de usuário podem usá-la; o PDV não deixa fechar a venda com natureza que o grupo do usuário não pode (ex.: Caixa não faz Doação)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): a detecção pela ocorrência 'sem financeiro' (TASK-205, OcorrenciaPdvService:199) não basta; o Fábio quer o bloqueio por grupo. Hoje tblnaturezaoperacao não tem nada de grupo.
<!-- SECTION:NOTES:END -->

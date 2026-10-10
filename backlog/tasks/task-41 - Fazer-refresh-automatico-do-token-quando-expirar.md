---
id: TASK-41
title: Fazer refresh automatico do token quando expirar
status: To Do
assignee: []
created_date: '2026-09-12 15:53'
updated_date: '2026-10-10 17:22'
labels:
  - negocios
dependencies: []
priority: high
type: bug
ordinal: 61000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: negocios/todo — secao IMPORTANTES (prefixo FIXME)
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 O token renova sozinho antes de vencer, sem o caixa ver o login no meio da venda
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026): hoje só renova pelo botão do menu do usuário (renovarToken em negocios/src/stores/auth.js); no 401 o axios limpa o token e abre o login.
<!-- SECTION:NOTES:END -->

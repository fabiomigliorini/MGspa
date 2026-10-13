---
id: TASK-187
title: >-
  O botão de sincronizar com o servidor está fazendo o "Negócio" ser atribuído a
  você.
status: To Do
assignee: []
created_date: '2026-09-29 21:39'
updated_date: '2026-10-10 19:43'
labels:
  - negocios
dependencies: []
priority: medium
type: bug
ordinal: 200000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Botão verde ao lado do CODNEGOCIO.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Fechar venda não reaberta grava a data e o usuário de quem fechou; fechar venda reaberta mantém os dois
- [ ] #2 Sincronizar negócio aberto não troca o usuário: fica quem criou até o fechar
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): regra do Fábio — quando não foi reaberto, o fechar atualiza a data e o codusuario; quando foi reaberto, o fechar não mexe nesses dois. Isso já é o que o código faz (PdvNegocioService::fechar, if (!$reaberta), TASK-30 commit 65ae4d593). O sintoma da task é outro ponto: PdvNegocioService::negocioAberto (linha ~224) grava codusuario = quem enviou em todo PUT de negócio aberto não reaberto.
<!-- SECTION:NOTES:END -->

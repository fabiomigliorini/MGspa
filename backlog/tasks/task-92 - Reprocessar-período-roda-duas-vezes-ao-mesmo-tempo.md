---
id: TASK-92
title: Reprocessar período demorado aparece como falha na fila sem ter falhado
status: To Do
assignee: []
created_date: '2026-09-12 15:56'
updated_date: '2026-10-10 19:06'
labels:
  - api
dependencies: []
priority: low
type: bug
ordinal: 8000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: api/database/NFE.md — "Já estava quebrado com 90; melhorou, não foi resolvido." Conferido no codigo: ReprocessarPeriodoJob.php:15 tem $timeout = 1800 e o .env tem REDIS_QUEUE_RETRY_AFTER=960. Enquanto o timeout for maior que o retry_after, a fila devolve o job ainda em execucao e ele roda duplicado.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Timeout do ReprocessarPeriodoJob menor que o retry_after da fila
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Conferido 15/09/2026: NAO corrigida. ReprocessarPeriodoJob.php:15 continua $timeout = 1800, nenhum commit apos o [ADD] original. .env de dev esta em QUEUE_CONNECTION=sync, entao o retry_after de prod nao e verificavel daqui.

Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): não roda duas vezes: o job tem tries = 1; quando a fila reentrega o job ainda rodando, o Laravel só registra falha (MaxAttemptsExceeded). 66 falhas assim de fev a jul/26 (última 28/07); nenhuma desde 07/08 (retry_after 90 -> 960, commit 50f1b1898). O timeout continua 1800 > 960.
<!-- SECTION:NOTES:END -->

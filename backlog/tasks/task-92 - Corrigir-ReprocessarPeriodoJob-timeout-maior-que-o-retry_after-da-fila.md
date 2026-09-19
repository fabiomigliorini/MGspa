---
id: TASK-92
title: 'Corrigir ReprocessarPeriodoJob: timeout maior que o retry_after da fila'
status: To Do
assignee: []
created_date: '2026-09-12 15:56'
updated_date: '2026-09-15 14:46'
labels:
  - api
dependencies: []
priority: high
type: bug
ordinal: 7000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: api/database/NFE.md — "Já estava quebrado com 90; melhorou, não foi resolvido." Conferido no codigo: ReprocessarPeriodoJob.php:15 tem $timeout = 1800 e o .env tem REDIS_QUEUE_RETRY_AFTER=960. Enquanto o timeout for maior que o retry_after, a fila devolve o job ainda em execucao e ele roda duplicado.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Conferido 15/09/2026: NAO corrigida. ReprocessarPeriodoJob.php:15 continua $timeout = 1800, nenhum commit apos o [ADD] original. .env de dev esta em QUEUE_CONNECTION=sync, entao o retry_after de prod nao e verificavel daqui.
<!-- SECTION:NOTES:END -->

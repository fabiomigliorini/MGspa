---
id: TASK-92
title: 'Corrigir ReprocessarPeriodoJob: timeout maior que o retry_after da fila'
status: To Do
assignee: []
created_date: '2026-09-12 15:56'
updated_date: '2026-09-12 17:13'
labels:
  - api
dependencies: []
priority: high
type: bug
ordinal: 92000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: api/database/NFE.md — "Já estava quebrado com 90; melhorou, não foi resolvido." Conferido no codigo: ReprocessarPeriodoJob.php:15 tem $timeout = 1800 e o .env tem REDIS_QUEUE_RETRY_AFTER=960. Enquanto o timeout for maior que o retry_after, a fila devolve o job ainda em execucao e ele roda duplicado.
<!-- SECTION:DESCRIPTION:END -->

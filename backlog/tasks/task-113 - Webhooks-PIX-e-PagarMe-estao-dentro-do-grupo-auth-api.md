---
id: TASK-113
title: 'Webhooks PIX e PagarMe estao dentro do grupo auth:api'
status: To Do
assignee: []
created_date: '2026-09-16 16:03'
labels:
  - api
dependencies: []
priority: medium
type: bug
ordinal: 100000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
routes/api.php:758 (pagar-me/webhook) e :778 (pix/webhook) ficam dentro do grupo Route::middleware(['auth:api']) sem withoutMiddleware. Callbacks externos sem Bearer devem estar recebendo 401. Verificar nos logs do nginx/laravel se chegam chamadas e com que status; se confirmado, liberar as rotas com validacao propria (assinatura/IP) em vez de auth:api. Hoje a confirmacao depende so do polling (pix:consultar a cada 10 min + botao Consultar). Origem: investigacao TASK-100 (2026-09-16).
<!-- SECTION:DESCRIPTION:END -->

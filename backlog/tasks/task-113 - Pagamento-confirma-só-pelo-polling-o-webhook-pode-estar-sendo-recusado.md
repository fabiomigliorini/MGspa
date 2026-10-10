---
id: TASK-113
title: 'Pagamento confirma só pelo polling: o webhook pode estar sendo recusado'
status: To Do
assignee: []
created_date: '2026-09-16 16:03'
updated_date: '2026-10-10 19:09'
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

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Webhook do PIX implementado (hoje PixWebhookService::processar é esqueleto: grava o JSON, enfileira o job e o foreach está vazio) ou a rota removida (ex-TASK-114)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): absorve a TASK-114. O webhook do PIX não está feito: a rota pix/webhook grava o arquivo e enfileira PixWebhookJob, mas PixWebhookService::processar não faz nada. Implementar = importar cada PIX recebido como o comando pix:consultar (PixService::importarPix), que desde a TASK-188 já cria o pagamento em Não resolvidos.
<!-- SECTION:NOTES:END -->

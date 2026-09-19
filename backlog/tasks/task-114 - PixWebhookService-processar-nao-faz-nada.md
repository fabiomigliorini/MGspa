---
id: TASK-114
title: 'PixWebhookService::processar nao faz nada'
status: To Do
assignee: []
created_date: '2026-09-16 16:03'
labels:
  - api
dependencies: []
priority: low
type: bug
ordinal: 101000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
app/Mg/Pix/PixWebhookService.php: o foreach sobre $obj->pix esta vazio (dd comentado), o resto do metodo esta comentado. O webhook do PIX e recebido, gravado em arquivo e ignorado; a confirmacao vem so do polling. Decidir: implementar (chamar PixService::importarPix por pix recebido, como faz o comando pix:consultar) ou remover a rota/job. Depende da task dos webhooks atras de auth:api. Origem: investigacao TASK-100 (2026-09-16).
<!-- SECTION:DESCRIPTION:END -->

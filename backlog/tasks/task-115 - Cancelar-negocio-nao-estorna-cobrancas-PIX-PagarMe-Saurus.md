---
id: TASK-115
title: Cancelar negocio nao estorna cobrancas PIX/PagarMe/Saurus
status: To Do
assignee: []
created_date: '2026-09-16 16:03'
labels:
  - api
dependencies: []
priority: low
type: bug
ordinal: 102000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
PdvNegocioService::cancelar estorna titulos e baixas de vale, mas deixa intactos os pagamentos com integracao=true e os pedidos/cobrancas (tblpixcob, tblpagarmepedido, tblsauruspedido). Um negocio cancelado com PIX ja recebido fica com dinheiro entrado e sem registro de devolucao. Decidir regra: bloquear cancelamento quando houver pagamento integrado confirmado (exigir devolucao/estorno antes), ou registrar o estorno. Origem: investigacao TASK-100 (2026-09-16).
<!-- SECTION:DESCRIPTION:END -->

---
id: TASK-115
title: Cancelar negocio nao estorna cobrancas PIX/PagarMe/Saurus
status: Done
assignee: []
created_date: '2026-09-16 16:03'
updated_date: '2026-10-10 17:21'
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

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026): já estava feito, fechada. Resolvida pela TASK-188 (#27, commit 1e6eeca87): cancelar a venda cancela só o pagamento manual; PIX e cartão integrados efetivados saem da venda e vão para Pagamentos não resolvidos (devolver ou amarrar). Cobrança ainda em aberto não é cancelada junto; se for paga depois, cai em Não resolvidos (fica assim).
<!-- SECTION:NOTES:END -->

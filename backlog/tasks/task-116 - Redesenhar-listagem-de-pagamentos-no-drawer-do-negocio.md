---
id: TASK-116
title: Redesenhar listagem de pagamentos no drawer do negocio
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-18 18:44'
updated_date: '2026-09-19 19:32'
labels:
  - negocios
dependencies: []
priority: medium
type: enhancement
ordinal: 120000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Listagem de pagamentos em TotalNegocio.vue esteticamente ruim: header Pagamentos+F8 desarmonico, captions cortadas, lixeira, segunda lista de cobrancas (PIX/PagarMe/Saurus) com visual diferente. Unificar numa lista so, acao de receber no Faltando.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Lista unica (pagamentos + cobrancas em aberto) com avatar/logo bandeira e 1 caption; sem header Pagamentos; sem pagamento = botao Receber F8; com pagamento = faixa Faltando clicavel (F8) / Troco; clique no pagamento abre PagamentoDialog (detalhe + Excluir) ou dialog da cobranca se integrado.
<!-- SECTION:PLAN:END -->

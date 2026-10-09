---
id: TASK-190
title: >-
  Caixa não tem como dar desconto automático por forma de pagamento nem por
  categoria de cliente
status: To Do
assignee: []
created_date: '2026-10-01 14:36'
updated_date: '2026-10-09 00:20'
labels:
  - negocios
dependencies:
  - TASK-188
priority: high
type: feature
ordinal: 203000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido do Fábio em 01/10/2026, na validação da TASK-188 (M5). Hoje o PDV só aceita um desconto digitado à mão no dinheiro (tecla − no Receber, sugestão 0%, rateado no valordesconto dos itens). A regra completa — desconto sugerido por forma de pagamento (dinheiro, PIX…) combinado com a categoria do cliente — será discutida em detalhe antes de implementar. Pontos já conhecidos: PIX QR não tem onde guardar desconto (tblpixcob); PIX por chave hoje é parcela, não pagamento; o desconto fica no pagamento (tblpagamento.desconto) e é rateado nos itens.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 O livro de ocorrências (TASK-205) usa a constante de 5% à vista (OcorrenciaService::DESCONTO_AVISTA) para apontar desconto acima do permitido: trocar pela regra por forma de pagamento e categoria de cliente
<!-- AC:END -->

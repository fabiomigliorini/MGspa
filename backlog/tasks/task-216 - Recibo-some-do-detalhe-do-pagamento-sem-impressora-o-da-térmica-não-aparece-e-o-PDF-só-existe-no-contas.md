---
id: TASK-216
title: >-
  Recibo some do detalhe do pagamento: sem impressora o da térmica não aparece e
  o PDF só existe no contas
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-10-10 21:18'
updated_date: '2026-10-10 21:30'
labels:
  - negocios
  - contas
dependencies: []
priority: low
type: bug
ordinal: 228000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
No PDV (negócios), o detalhe do pagamento escondia o botão do recibo térmico quando o PDV não tinha impressora térmica configurada, e o recibo em PDF (Recibo, Recibo de Recebimento, Recibo de Pagamento) só existia no contas. No contas também escondia os recibos quando não havia título amarrado, quando cancelado e conforme recebimento/pagamento. Pedido do Fábio (10/10/2026): mostrar sempre os dois recibos (térmica e PDF) nos dois apps; sem pré-requisito, a ação recusa com mensagem.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Detalhe do pagamento mostra sempre os botões de recibo (PDF e térmica) no negócios e no contas
- [ ] #2 Recibo da térmica abre o cupom para ver; no PDV o Imprimir manda para a térmica e, sem impressora, recusa com mensagem
- [ ] #3 Recibo que não se aplica (cancelado, sem título, sem recebimento/pagamento) recusa com a mensagem do servidor em vez de sumir
- [x] #4 Número do título na parcela do diálogo de pagamento do negócio sai como link da casa (azul, sem sublinhado), não roxo e sublinhado do navegador
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Recibos saem dos slots dos apps e vão para o MgPagamentoDetalhe, sem v-if: Recibo / Recebimento / Pagamento (PDF A4, v1/pagamento/{id}/recibo*) e Recibo térmica (cupom, GET v1/pdv/pagamento/recibo/{id}, abrirPdf size cupom). Prop imprimirTermica: o negócios passa a função que manda para a térmica (recusa 'Nenhuma impressora térmica selecionada!'); no contas o cupom só abre. Arquivos: components/MgPagamentoDetalhe.vue, negocios/src/pages/PagamentoPage.vue, contas/src/pages/pagamento/Detalhe.vue.

Link da parcela: negocios/src/components/offline/PagamentoDialog.vue, o <a> do título ganhou class text-primary e text-decoration none (mesmo padrão do MgPagamentoDetalhe).
<!-- SECTION:NOTES:END -->

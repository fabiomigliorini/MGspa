---
id: TASK-225
title: Alerta de desconto acima do permitido dispara em venda com os 5% à vista
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-13 23:00'
updated_date: '2026-10-13 23:01'
labels:
  - negocios
dependencies: []
priority: medium
type: bug
ordinal: 236000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Relato do Fábio (13/10/2026), depois de ligar o monitoramento dos caixas: venda #04640264 (Botânico Caixa 04) com produtos R$ 67,15, desconto de 5% (R$ 3,36) e pago no débito gerou a ocorrência 'Desconto de R$ 3,36 (5,0%), permitido R$ 3,19'. Qualquer venda com os 5% pagos à vista (dinheiro, PIX, débito) dispara o alerta.

Causa: em OcorrenciaPdvService::desconto() (TASK-205) a base dos 5% era a soma do principal dos pagamentos à vista, que já vem líquido do desconto (5% de 63,79 = 3,19). A base certa é o bruto, produtos + face dos vales, a mesma do % de desconto no PDV (TotalNegocio.vue, baseRateio).

Correção: bruto = valorprodutos + valorvales; fração à vista = principal à vista / (valortotal - valorjuros), limitada a 1; permitido à vista = 5% do bruto × fração. O desconto do cadastro do cliente e o % exibido na ocorrência também passam a usar o bruto.

As ocorrências já registradas antes da correção ficam na lista (o cálculo roda só no fechamento); o gerente dá o visto nelas.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Venda com 5% de desconto paga toda à vista não gera ocorrência de desconto
- [x] #2 Venda com desconto acima de 5% gera a ocorrência com o permitido calculado sobre produtos + vales
- [x] #3 Venda com pagamento misto (à vista + crédito/a prazo) permite 5% só sobre a parte paga à vista
<!-- AC:END -->

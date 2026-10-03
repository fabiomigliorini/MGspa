---
id: TASK-196
title: Pagamento a fornecedor é feito no site do banco e lançado à mão no sistema
status: To Do
assignee: []
created_date: '2026-10-03 15:13'
labels:
  - contas
  - api
dependencies:
  - TASK-39
priority: medium
type: feature
ordinal: 209000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Milestone M15 do plano do fechamento de caixa (backlog/docs/doc-3 - Plano-do-fechamento-de-caixa-por-milestones.md, seção M15). Ordem de pagamento (PIX por chave/dados/QR, boleto, TED) enviada pela API do banco como pagamento de saída pendente até o banco confirmar; lote + item, chave própria sequencial, 'aguardando liberação'; devolução como pagamento contrário. Levantamento de 29/09/2026 (parte de memória, conferir campos e estados ao implementar): BB Pagamentos em Lote (lote + item, liberação, webhook, mTLS A1), Itaú SISPAG (pré-aprovado × pós-autorizado), Bradesco (boleto, tributo, TED, PIX), Sicredi sem API pública de pagamento. Relacionada: TASK-115 (cancelar negócio não estorna PIX/PagarMe/Saurus) cobre o mesmo mecanismo de devolução pela API do lado da venda.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Ordem de pagamento (PIX, boleto, TED) enviada ao banco fica pendente até o banco confirmar
- [ ] #2 Pagamentos agrupados em lote, com o estado 'aguardando liberação' visível
- [ ] #3 Devolução registrada como pagamento contrário
- [ ] #4 Devolução de PIX e cancelamento de cartão feitos pela API da operadora/banco
<!-- AC:END -->

---
id: TASK-195
title: >-
  Recebimento de cartão não aparece no caixa nem no banco e não dá para conferir
  com o extrato da maquininha
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
ordinal: 208000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Milestone M14 do plano do fechamento de caixa (backlog/docs/doc-3 - Plano-do-fechamento-de-caixa-por-milestones.md, seção M14). Hoje o razão do portador (M10) lança dinheiro, PIX, depósito, transferência e boleto, mas não crédito/débito: a venda no cartão não aparece na adquirente, o repasse ao banco não é lançado e não há como conferir com o extrato da adquirente nem com a fatura do cartão da empresa. Escopo a definir em detalhe quando chegar a vez; ligar PortadorMovimentoService::lancar para os meios crédito e débito.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Prazos e taxas cadastrados por adquirente/maquineta
- [ ] #2 Venda no cartão lança na adquirente por parcela, na data em que cai (D+1 débito, D+30… crédito)
- [ ] #3 Repasse da adquirente para o banco lançado como transferência
- [ ] #4 Taxas e débitos da adquirente lançados como pagamento sem documento
- [ ] #5 Fatura do cartão da empresa como período com vencimento, compras parceladas caindo nas faturas futuras
- [ ] #6 Conciliação do razão com o extrato, com importação do extrato da adquirente e da fatura
<!-- AC:END -->

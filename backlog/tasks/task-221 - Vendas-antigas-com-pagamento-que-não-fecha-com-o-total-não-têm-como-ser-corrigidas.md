---
id: TASK-221
title: >-
  Vendas antigas com pagamento que não fecha com o total não têm como ser
  corrigidas
status: To Do
assignee: []
created_date: '2026-10-12 01:37'
updated_date: '2026-10-12 02:12'
labels:
  - contas
dependencies:
  - TASK-218
priority: high
type: bug
ordinal: 232000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido do Fábio (11/10/2026), saído da definição da tela Divergências (TASK-218).

Venda fechada em que pagamentos + parcelas não somam o total. No dev, o histórico todo tem 1.216.225. O script da TASK-218 (api/database/pagamento_historico.sql) corrige três padrões, só antes do go-live:
- troco em dinheiro não gravado: 1.158.671 vendas (só quando o troco é menor que o maior pagamento em dinheiro);
- pagamento repetido: 26.043 vendas, 26.052 cópias canceladas (só a cópia lançada à mão, sem baixa nem cheque, e quando sem ela a venda bate);
- troco do vale: 2.107 (o vale sem baixa de título).
Desde o go-live (02/10/2026) não há nenhum caso.

Depois do script sobram 29.404 vendas antigas no dev, que aparecem em Divergências. A tela Venda com diferença (fechamento/venda/:id) não conserta venda de antes do go-live: incluir ou trocar para dinheiro exige o caixa daquele momento (PagamentoCorrecaoService::sessaoDoDinheiro: 'Não havia caixa aberto na gaveta do PDV quando o pagamento foi feito').

Ainda não se sabe se a culpa é do sistema antigo ou da migração (TASK-188). A tblnegocioformapagamento hoje é uma view em cima da tblpagamento, e não há cópia do dado original no dev.

Como corrigir: decidir depois (Fábio, 11/10/2026).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Vendas com sobra por outro motivo (5.698 no dev) corrigidas
- [ ] #2 Vendas fechadas sem nenhum pagamento nem parcela (767 no dev) corrigidas
- [ ] #3 Vendas com pagamento a menos (285 no dev) corrigidas
- [ ] #4 Vendas de entrega com a parcela e também o pagamento do mesmo valor (80 no dev) corrigidas
- [ ] #5 Venda de antes do go-live tem como ser corrigida (hoje a Venda com diferença exige o caixa daquele momento)
- [ ] #6 Vendas em que o dinheiro a mais é do tamanho de um pagamento em dinheiro inteiro (21.284 no dev; não é troco, parece dinheiro lançado junto com outra forma) corrigidas
- [ ] #7 Vendas com pagamento repetido integrado, com baixa ou cheque, ou que não bate exato sem a cópia (o script não cancela) corrigidas
<!-- AC:END -->

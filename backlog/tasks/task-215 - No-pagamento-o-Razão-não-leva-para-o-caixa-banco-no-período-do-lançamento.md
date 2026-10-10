---
id: TASK-215
title: 'No pagamento, o Razão não leva para o caixa/banco no período do lançamento'
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-10 20:53'
updated_date: '2026-10-10 20:54'
labels:
  - contas
dependencies: []
priority: low
type: enhancement
ordinal: 227000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Na tela do pagamento (contas e negócios), o card RAZÃO mostra o portador e o período onde o dinheiro caiu, mas não é clicável: para conferir o caixa/banco é preciso procurar o portador e o período à mão. Pedido do Fábio em 10/10/2026.

Implementação: components/MgPagamentoDetalhe.vue ganha a prop toPortador (mesmo padrão do toTitulo); o contas passa a rota portador-detalhe com codportador/codportadorperiodo; sem a prop (negócios), o link abre o contas em outra aba.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Clicar numa linha do Razão abre o portador já no período do lançamento
<!-- AC:END -->

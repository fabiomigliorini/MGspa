---
id: TASK-215
title: 'No pagamento, o Razão não leva para o caixa/banco no período do lançamento'
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-10 20:53'
updated_date: '2026-10-10 21:15'
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
- [x] #2 Hover da linha do Razão não vaza pelos cantos arredondados do card (corrigido global: .q-card e .q-list.rounded-borders com overflow hidden no app.scss dos 6 apps)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Hover vazando pelos cantos: o q-focus-helper do q-item é retangular e q-card/q-list não cortavam o transbordo com o $generic-border-radius de 13px. Regra global no src/css/app.scss de agro, contas, estoque, negocios, notas e pessoas. Card de dialog segue com overflow auto (.q-dialog__inner > div tem mais especificidade).
<!-- SECTION:NOTES:END -->

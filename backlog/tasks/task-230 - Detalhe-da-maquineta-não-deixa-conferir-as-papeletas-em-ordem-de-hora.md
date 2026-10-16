---
id: TASK-230
title: Detalhe da maquineta não deixa conferir as papeletas em ordem de hora
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-16 16:16'
updated_date: '2026-10-16 22:22'
labels:
  - contas
dependencies: []
priority: low
type: feature
ordinal: 241000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
O card Detalhe do período da maquineta (contas, /maquineta/:cod/:lote) lista as vendas na ordem do borderô (modalidade → bandeira → hora). Para conferir com as papeletas, que ficam empilhadas em ordem de hora, o card ganhou duas abas: Por bandeira (como era) e Por hora (lista única cronológica, com a modalidade e a bandeira na linha). Pedido do Fábio em 15/10/2026.

Arquivo: contas/src/components/maquineta/PeriodoLancamentos.vue. O bloco de cancelamento de outro período fica no fim nas duas abas.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Card Detalhe com as abas Por bandeira (ordem atual) e Por hora (cronológica, modalidade e bandeira na linha)
- [x] #2 Linha longa (Por hora, com caixa e venda) não empurra o valor para fora do card: o texto corta com reticências
- [x] #3 Cada venda em duas linhas: em cima a modalidade (Débito, Crédito à vista, Crédito 6x) e o valor; embaixo bandeira, NSU, autorização, caixa e venda — cabe no celular
- [x] #4 Clicar na venda leva sempre ao pagamento (antes, às vezes ia para o negócio, às vezes para o pagamento)
- [x] #5 Clicar num total ou numa bandeira do resumo filtra as vendas do detalhe
- [x] #6 Resumo termina no Total, verde se bate com o borderô e vermelho se não; com diferença, mostra o Informado (cinza) e a Diferença (negrito, vermelho)
- [x] #7 Foto, resumo e detalhe lado a lado: xs 12/12/12, sm 5/7/12, de md em diante 3/4/5
<!-- AC:END -->

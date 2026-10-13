---
id: TASK-223
title: >-
  Período da maquineta não guarda os próprios lançamentos, como o período do
  caixa
status: To Do
assignee: []
created_date: '2026-10-13 16:28'
labels:
  - contas
dependencies: []
priority: low
type: chore
ordinal: 234000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido do Fábio (13/10/2026), na investigação do painel (TASK-203): criar a tabela de movimento do período da maquineta, igual à do portador.

Hoje o período da maquineta (tblmaquinetalote) não tem tabela filha: o pagamento aponta direto para ele por duas colunas, tblpagamento.codmaquinetalote (onde o cartão entrou) e tblpagamento.codmaquinetalotecancelamento (onde o cancelamento caiu). Quem precisa do conteúdo do período lê o pagamento: MaquinetaLoteService::sistema (o sistema do borderô), ::totais, ::resumo (lista de Maquinetas e painel: período aberto com cartão) e a troca de período ao alterar a data (TASK-204, LancamentoDataService).

O período do caixa (tblportadorperiodo) tem a filha tblportadormovimento (codportadorperiodo): pagamento (tipo P), transferência (T), ajuste (A) e itens do caixa (I/M), além de tblpagamento.codportadorperiodo.

Relacionada, mas outra coisa: a TASK-195 lança o cartão no razão da adquirente (parcelas, repasse ao banco, extrato); esta é o período da maquineta, que se confere com o borderô.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Tabela de movimento do período da maquineta, como a tblportadormovimento: o cartão e o cancelamento viram movimento do período
- [ ] #2 Conferência do borderô, lista de Maquinetas, painel e alterar data leem o movimento do período em vez do pagamento direto
- [ ] #3 Períodos que já existem ganham os movimentos dos pagamentos que apontam para eles
<!-- AC:END -->

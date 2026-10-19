---
id: TASK-239
title: >-
  Entrada de chips no caixa ocupa duas linhas por item e a contagem mistura as
  operadoras
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-16 23:01'
updated_date: '2026-10-18 13:55'
labels:
  - components
dependencies: []
priority: low
type: enhancement
ordinal: 242000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Na entrada de item do caixa (chips de celular), cada item ocupava duas linhas (descrição em cima, preço e quantidade embaixo) num diálogo estreito, e o + Linha não levava o foco para a linha nova. Na contagem e na saída do item, as linhas vinham por preço (Claro 15 · Vivo 20 · Claro 30), misturando as operadoras; preço de 3 dígitos (100,00) aparecia antes do 15,00, porque a chave era comparada como texto.

Tela: contas e negocios, período do caixa > Lançar > Entrada/Saída do item; contagem inicial/final do item.

Front: components/caixa/ItemCaixaDialog.vue — linha em col-12 col-sm-6 / col-6 col-sm-3 / col-6 col-sm-3, divisória lt-sm entre os itens, entrada abre com 600px (com 400px os três não cabem), descrição em MgSelect, autofocus na última linha.
API: CaixaItemService::linhas e ::somarLinhas ordenam por descrição (minúscula) e preço numérico, no lugar do ksort da chave preco|descricao.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Entrada do item: descrição, quantidade e preço numa linha só do sm pra cima; no xs em duas, com divisória entre os itens
- [x] #2 + Linha leva o foco para a descrição da linha nova
- [x] #3 Contagem e saída do item listam as linhas por descrição e depois preço
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Renumerada de TASK-231 para TASK-239 em 18/10/2026: o número colidiu com a TASK-231 do Eduardo (Build da API mostra avisos sobre a bateria de testes do agro). O commit c98ce53ce (16/10/2026) cita esta task como TASK-231.
<!-- SECTION:NOTES:END -->

---
id: TASK-240
title: >-
  Chip lançado por engano e estornado continua aparecendo nas descrições do item
  do caixa e como sugestão na entrada
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-16 23:35'
updated_date: '2026-10-18 13:55'
labels:
  - contas
dependencies: []
priority: low
type: bug
ordinal: 243000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Em produção, no item Chips de Celular, alguém lançou um chip com a descrição "Bilhete agora" por engano. O lançamento foi estornado, mas a descrição continuou na lista Descrições da tela do item (contas, /caixa-item/1) e como sugestão na Entrada de chips. Mesmo caso do "Tim 1,00". Também ficavam para sempre na sugestão as descrições que saíram de uso.

Causa: CaixaItemService::tipos() (lista Descrições) pegava toda linha de tblportadormovimento.itens do item, inclusive as canceladas; ::descricoes() (typeahead da entrada) juntava entradas e saídas de qualquer estado e todas as contagens iniciais e finais de todos os períodos.

Correção (a5d782391): tipos() passa a sair de saldos(), o mesmo saldo por caixa da tela do item (última contagem fechada + entradas e saídas não canceladas depois dela), só as linhas com quantidade > 0 em algum caixa; descricoes() usa tipos() (distintas sem diferenciar maiúscula, até 20). Efeito: tipo sem saldo some da lista, então não dá mais para renomear por ali (fica só no histórico dos fechamentos); item zerado em todos os caixas fica sem sugestão.

Desempenho (ac0464689): saldos() buscava as entradas depois do último fechamento varrendo todas as entradas do item na história de cada caixa. Agora entra pelos períodos depois do último fechado (ANY(ARRAY(...)) pelo índice de codportadorperiodo). Simulação de um ano em tabelas temporárias (6.935 fechamentos, 40 mil movimentos): realista (4 mil de chips) 12,5ms → 1,9ms; pior caso (40 mil de chips) 157ms → 9,3ms; a consulta antiga das sugestões dava 228ms/520ms. Resultado conferido igual ao da consulta antiga nos dados simulados e nos reais do dev.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Lançamento estornado não aparece nas Descrições do item nem na sugestão da entrada
- [x] #2 Descrições e sugestão mostram só o que tem saldo em algum caixa
- [x] #3 Consulta do saldo continua rápida com um ano de fechamentos e movimentos
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Renumerada de TASK-232 para TASK-240 em 18/10/2026: o número colidiu com a TASK-232 do Eduardo (Filtros da tela de Pagamentos não mudam a listagem). O commit 840cd3282 (16/10/2026) cita esta task como TASK-232.
<!-- SECTION:NOTES:END -->

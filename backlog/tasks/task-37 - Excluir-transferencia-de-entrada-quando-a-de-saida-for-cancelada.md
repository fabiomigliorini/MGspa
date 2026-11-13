---
id: TASK-37
title: Excluir transferencia de entrada quando a de saida for cancelada
status: In Progress
assignee: []
created_date: '2026-09-12 15:53'
updated_date: '2026-11-12 18:48'
labels:
  - notas
dependencies: []
priority: high
type: feature
ordinal: 8000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Quando emite uma nota fiscal de transferencia de saida, o sistema espelha a entrada... mas se essa nota é cancelada, precisa remover esse "espelho"... precisa fazer uma job pra fazer isso automaticametne em 2o plano... e também rpecisa fazer pra outras naturezaas que possam ter o mesmo espelhamento....
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Investigação de 09/11/2026 (não vamos fazer agora): só a natureza com transferencia = true espelha (15 Transferencia Saida → 16 Transferencia Entrada), pelo comando de hora em hora nota-fiscal:transferencia-entrada (NotaFiscalTransferenciaService::gerarTransferenciaEntrada). A entrada não tem chave estrangeira para a saída: é a mesma nfechave com emitida = false na filial de destino. Nada roda depois do cancelamento hoje. No dev há 38 entradas de saída cancelada ainda lançadas (2024: 12, 2025: 19, 2026: 7), quase todas com movimento de estoque e nenhuma com devolução. Apagar a entrada exige apagar o movimento de estoque dos itens dela (chave sem cascata), limpar o codestoquemovimentoorigem dos dependentes e recalcular o custo médio no MGLara; o job do MGLara não serve depois de apagar, porque faz findOrFail no item.
<!-- SECTION:NOTES:END -->

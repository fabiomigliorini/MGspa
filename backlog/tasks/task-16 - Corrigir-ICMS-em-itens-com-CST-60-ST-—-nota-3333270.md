---
id: TASK-16
title: Nota com item CST 60 é rejeitada (531) e precisa ser inutilizada
status: To Do
assignee: []
created_date: '2026-09-12 15:53'
updated_date: '2026-10-10 19:07'
labels:
  - notas
dependencies: []
priority: high
type: bug
ordinal: 18000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: notas/todo.md — "https://notas.mgpapelaria.com.br/nota/3333270 ficou com valor de icms em alguns itens com cst 60 (ST)" (aparecia duplicado no arquivo)
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 A tela zera base, percentual e valor do ICMS do item CST 30/40/41/50/60
- [ ] #2 O servidor zera a base do item CST 60 ao recalcular a tributação e ao trocar a natureza (NotaFiscalProdutoBarraService::calcularTributacao)
- [ ] #3 A devolução não copia a base de ICMS do item CST 60 da nota de origem (NotaFiscalDevolucaoService)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): absorve a TASK-23. Causa: NotaFiscalItemService.php:121 soma o icmsbase de todos os itens em tblnotafiscal.icmsbase e NFePHPMakeService.php:735 manda como vBC do ICMSTot; o ICMS60 do item não leva vBC -> 531. Nota 3333270: icmsbase 122,33 = 107,25 (CST 00) + 15,08 (dois itens CST 60). O #1 foi feito no commit cf3b3d4ad (25/02/2026, useNotaFiscalItemCalculos.js). Em 2026: 14 notas com item CST 30-60 e base > 0, todas INU (13 entre 17/01 e 26/02; 1 em 15/04, nota 3418365, devolução de compra com base copiada da origem); de março a julho nenhuma em ~7,7 mil NF-e com CST 60.
<!-- SECTION:NOTES:END -->

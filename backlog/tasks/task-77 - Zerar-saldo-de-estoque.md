---
id: TASK-77
title: Zerar saldo de estoque
status: Done
assignee: []
created_date: '2026-09-12 15:56'
updated_date: '2026-10-10 19:06'
labels:
  - estoque
dependencies: []
priority: medium
type: feature
ordinal: 80000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: estoque/docs/BLUEPRINT_MIGRACAO_MGLARA.md — Fase B.2. Portar EstoqueController@zeraSaldo; UI = acao em estoque-saldo. Conferido: nao existe no api.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): já estava feito. Zerar um produto num local: botão Zerar saldo na Conferência do app estoque (POST v1/estoque-saldo-conferencia/zerar-produto, EstoqueSaldoConferenciaController@zerarProduto). Zerar em lote do MGLara não é portado.
<!-- SECTION:NOTES:END -->

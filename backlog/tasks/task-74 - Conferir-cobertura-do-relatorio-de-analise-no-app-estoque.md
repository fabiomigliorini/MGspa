---
id: TASK-74
title: Conferir cobertura do relatorio de analise no app estoque
status: Done
assignee: []
created_date: '2026-09-12 15:56'
updated_date: '2026-10-10 19:06'
labels:
  - estoque
dependencies: []
priority: medium
type: feature
ordinal: 77000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: estoque/docs/BLUEPRINT_MIGRACAO_MGLARA.md — Fase A. Backend ja tem comparativo-vendas, fisico-fiscal e transferencias; falta confirmar o relatorio "analise" em relatorios/Index.vue. Conferido: nao ha arquivo com 'analise' em estoque/src.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): a grade de Saldo do estoque cobre o relatório de Análise do MGLara (filtros negativo/positivo, abaixo/acima do mínimo e do máximo, depósito, marca; desce até a variação com mín/máx — EstoqueSaldoFiltrosDrawer). A versão impressa não é portada.
<!-- SECTION:NOTES:END -->

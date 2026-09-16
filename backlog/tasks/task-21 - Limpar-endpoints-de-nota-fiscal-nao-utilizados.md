---
id: TASK-21
title: Limpar endpoints de nota-fiscal nao utilizados
status: To Do
assignee: []
created_date: '2026-09-12 15:53'
updated_date: '2026-09-15 15:11'
labels:
  - notas
dependencies: []
priority: low
type: feature
ordinal: 6000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: notas/todo.md — bloco pendente
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Conferido 15/09/2026: NAO feita. Levantamento: 6 rotas do NotaFiscalTransferenciaController sem NENHUMA referencia no repo (notas, contas, negocios, pessoas, quasar): dashboard-transferencia, gera-transferencias/{codfilial}, notas-por-emitir, notas-nao-autorizadas, notas-emitidas, notas-lancadas — e ainda declaradas 2x (api.php:688-693 e 1120-1125). As demais rotas nota-fiscal tem uso no front.
<!-- SECTION:NOTES:END -->

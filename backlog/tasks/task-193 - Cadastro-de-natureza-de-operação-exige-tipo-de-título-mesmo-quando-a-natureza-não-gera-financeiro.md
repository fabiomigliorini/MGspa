---
id: TASK-193
title: >-
  Cadastro de natureza de operação exige tipo de título mesmo quando a natureza
  não gera financeiro
status: To Do
assignee: []
created_date: '2026-10-02 15:36'
labels:
  - notas
dependencies: []
priority: high
type: feature
ordinal: 206000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido do Fábio em 02/10/2026, na limpeza dos tipos de título (M8.1 do doc-3, TASK-188). tblnaturezaoperacao.codtipotitulo é NOT NULL e o NaturezaOperacaoRequest exige o tipo, mesmo para natureza com financeiro = false (transferência, remessa, uso e consumo, bonificação...). Isso obriga a manter tipos de título que não servem para nada só para a natureza ter onde apontar. Deve ficar: tipo de título obrigatório só quando a natureza gera financeiro; sem financeiro, vazio (DDL: coluna aceita nulo; request required_if financeiro; quem gera título pela natureza só lê o tipo quando financeiro).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Natureza de operação sem financeiro pode ser salva sem tipo de título
- [ ] #2 Natureza de operação com financeiro continua exigindo o tipo de título
- [ ] #3 As naturezas sem financeiro que hoje apontam para tipo de título ficam sem tipo
<!-- AC:END -->

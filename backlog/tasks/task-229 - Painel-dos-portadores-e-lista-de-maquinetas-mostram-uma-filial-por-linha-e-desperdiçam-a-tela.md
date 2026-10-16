---
id: TASK-229
title: >-
  Painel dos portadores e lista de maquinetas mostram uma filial por linha e
  desperdiçam a tela
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-16 15:49'
updated_date: '2026-10-16 15:49'
labels:
  - contas
dependencies: []
priority: low
type: enhancement
ordinal: 240000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
No contas, as telas /portador e /maquineta empilhavam um card por filial ocupando a largura toda, obrigando a rolar muito para ver todas as filiais. Pedido do Fábio em 15/10/2026: os cards das filiais passam a ficar em grid col-xs-12 col-sm-6 col-md-4 (1 por linha no celular, 2 no tablet, 3 no desktop).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Painel dos portadores (/portador) mostra os cards das filiais em grid col-xs-12 col-sm-6 col-md-4
- [x] #2 Lista de maquinetas (/maquineta) mostra os cards das filiais em grid col-xs-12 col-sm-6 col-md-4
<!-- AC:END -->

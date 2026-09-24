---
id: TASK-88
title: Historico de salario do colaborador
status: Done
assignee: []
created_date: '2026-09-12 15:56'
updated_date: '2026-09-24 12:24'
labels:
  - pessoas
dependencies: []
priority: low
type: feature
ordinal: 182000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: blueprint-rh-indicadores .md — secao Pos-MVP. Conferido: nao existe no api.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Conferido 15/09/2026: PARCIAL por efeito colateral. tblcolaboradorcargo tem inicio/fim/salario por vinculo de cargo (CardColaboradorCargo.vue lista), entao ha historico quando o colaborador TROCA de cargo; alteracao de salario dentro do mesmo cargo sobrescreve. Nao existe estrutura dedicada de historico salarial.
<!-- SECTION:NOTES:END -->

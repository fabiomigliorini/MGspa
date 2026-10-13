---
id: TASK-71
title: 'NotaFiscalFormPage: carregar as opcoes via API'
status: Done
assignee: []
created_date: '2026-09-12 15:54'
updated_date: '2026-10-10 17:21'
labels:
  - notas
dependencies: []
priority: medium
type: feature
ordinal: 74000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: marcacao no codigo — notas/src/pages/NotaFiscalFormPage.vue:180
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026): já estava feito, fechada. Os selects que dependem de API já são MgSelect* (filial, local, natureza, pessoa, estado); modelo e frete são listas fixas. Removidos loadOptions() e operacoesOptions, que nenhum lugar do template usava.
<!-- SECTION:NOTES:END -->

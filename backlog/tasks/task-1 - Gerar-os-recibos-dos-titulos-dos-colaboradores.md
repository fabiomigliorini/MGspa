---
id: TASK-1
title: Gerar os recibos dos titulos dos colaboradores
status: Done
assignee: []
created_date: '2026-09-12 15:53'
updated_date: '2026-09-15 15:11'
labels:
  - pessoas
dependencies: []
priority: medium
type: feature
ordinal: 1000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: pessoas/todo — "TODO: Gerar os recibos dos titulos dos colaboradores"
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Conferido 15/09/2026: implementado. Rotas rh/acerto/recibos, /{codperiodocolaborador}/recibos e /{codperiodocolaboradoracerto}/recibo (api.php:1319-1321), AcertoReciboPdf.php, botoes em PeriodoDashboard.vue e ColaboradorDetalhe.vue. Commits 33832aa1 (30/07/2026), 16e0a4e8, 9d0a13ca (08/2026). Todo era de 28/02/2026.
<!-- SECTION:NOTES:END -->

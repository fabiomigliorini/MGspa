---
id: TASK-259
title: Produto aparece em marca diferente dependendo da tela
status: To Do
assignee: []
created_date: '2026-10-25 22:55'
labels:
  - estoque
dependencies: []
priority: low
type: chore
ordinal: 268000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
A variação pode ter marca própria (tblprodutovariacao.codmarca, 94 variações ativas em 25/10/2026), e cada tela usa uma regra: MarcaService usa coalesce(pv.codmarca, p.codmarca), o PDF de transferências e o saldo usam p.codmarca. Decisão do Fábio (25/10): eliminar a marca da variação do sistema; vale só a marca do produto. O destino das 94 variações com marca própria (virar produto próprio ou ficar na marca do produto) se decide aqui. Surgiu no plano da TASK-258.
<!-- SECTION:DESCRIPTION:END -->

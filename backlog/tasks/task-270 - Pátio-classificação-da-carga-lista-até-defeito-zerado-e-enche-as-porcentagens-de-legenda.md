---
id: TASK-270
title: >-
  Pátio: classificação da carga lista até defeito zerado e enche as porcentagens
  de legenda
status: To Do
assignee: []
created_date: '2026-11-05 20:19'
labels:
  - agro
dependencies: []
priority: low
type: enhancement
ordinal: 278000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido do Fábio (05/11/2026). O card Classificação da carga no pátio (/carga/:uuid) lista todos os parâmetros num grid, inclusive os de leitura 0% ou vazia, com legenda embaixo de cada porcentagem ('acima da tolerância', '− X kg'). Pedido: uma linha no mesmo desenho do card Pesagem (TASK-269) só com os parâmetros que o grão tem (leitura > 0), sem legenda, e um botão Detalhes no estilo do lápis de editar que abre um diálogo com o desconto de cada parâmetro (Parâmetro · Leitura · Tolerância · Desconto) e o total. Arquivo: agro/src/components/carga/CargaBlocoClassificacao.vue. O diálogo de digitação da classificação não muda. Começa depois da validação da TASK-269.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 O card mostra, no desenho da pesagem, só os parâmetros com leitura acima de zero
- [ ] #2 O card não tem legenda embaixo das porcentagens
- [ ] #3 O botão Detalhes, no estilo do lápis, abre o desconto de cada parâmetro e o total
<!-- AC:END -->

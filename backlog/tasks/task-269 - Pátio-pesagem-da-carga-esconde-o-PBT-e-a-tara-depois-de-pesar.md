---
id: TASK-269
title: 'Pátio: pesagem da carga esconde o PBT e a tara depois de pesar'
status: In Progress
assignee:
  - '@eduardo'
created_date: '2026-11-05 20:19'
updated_date: '2026-11-05 20:26'
labels:
  - agro
dependencies: []
priority: low
type: enhancement
ordinal: 277000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido do Fábio (05/11/2026). Na tela da carga no pátio (/carga/:uuid), o card Pesagem mostra PBT e Tara só antes de pesar; depois de pesar eles somem e o card passa a mostrar Bruto · Desconto · Líquido · Sacas. Pedido: uma linha só, na ordem da balança, com PBT · Tara · PB · Descontos · Líquido. Arquivo: agro/src/components/carga/CargaBlocoPesagem.vue. Sacas sai do card (continua no resumo da direita, CargaResumo, e no ticket). O diálogo de digitação da pesagem não muda.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 A pesagem mostra numa linha PBT · Tara · PB · Descontos · Líquido
- [x] #2 O que ainda não foi pesado aparece como —
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
05/11/2026 — CargaBlocoPesagem.vue: os dois ramos do card (antes de pesar: PBT/Tara; depois: Bruto/Desconto/Líquido/Sacas) viraram uma linha só com PBT · Tara · PB · Descontos · Líquido, sempre os 5, na ordem da balança (computed pesos; PB/Descontos/Líquido do mesmo calc do form). O que não foi pesado sai '—' em cinza. Desktop: 5 lado a lado; celular: 3 + 2 centrado. Sacas saiu do card (segue no resumo da direita, no ticket e no aviso de finalização); o provide('sacasLiquido') do CargaForm ficou sem consumidor e saiu. Diálogo de digitação sem mudança. Conferido com página temporária no Vite + Chrome headless (1280 e 390 px) com a carga 21494 real do dev: 70.000 · 15.000 · 55.000 · 5.171 · 49.829 kg (bate com o servidor); só PBT: o resto '—'; transferência sem classificação: Descontos 0 kg. Teste: Ctrl+Shift+R e abrir /carga/a870aae1-b74b-47ff-8463-0d9b85a0b3e3 (carga 21494).
<!-- SECTION:NOTES:END -->

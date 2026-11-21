---
id: TASK-292
title: Gerar nota de serviço a partir do negócio
status: To Do
assignee: []
created_date: '2026-11-21 18:28'
updated_date: '2026-11-21 18:49'
labels:
  - negocios
dependencies: []
priority: high
type: feature
ordinal: 303000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Hoje a nota de serviço (NFS-e, TASK-279) só é digitada no app notas. A TASK-279 deixou para depois gerar a nota a partir do negócio, como já se faz com a NF-e/NFC-e.

Origem: pedido do Fábio em 21/11/2026.

Pontos a decidir antes de implementar (herdados das decisões da TASK-279):
- Uma NFS-e leva um serviço só (um código de tributação nacional/NBS por nota). Falta decidir o que fazer com um negócio que tenha vários itens de serviço, ou produtos e serviços misturados.
- Hoje o código de tributação nacional e a NBS são escolhidos na própria nota. A TASK-279 deixou como FUTURO o produto do tipo Serviço guardar esses dados para a nota copiar, e é isso que permite gerar a nota a partir do negócio.
- No Simples da FDF, o % aproximado de tributos (e a alíquota do ISS, quando retido) é digitado em cada nota. Falta definir de onde vem quando a nota nasce do negócio.
<!-- SECTION:DESCRIPTION:END -->

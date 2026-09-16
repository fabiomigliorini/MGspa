---
id: TASK-12
title: Adicionar campo comissaocaixa na manutencao de cargo
status: To Do
assignee: []
created_date: '2026-09-12 15:53'
updated_date: '2026-09-15 15:11'
labels:
  - pessoas
dependencies: []
priority: medium
type: feature
ordinal: 4000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: pessoas/todo — "TODO Adicionar campo comissaocaixa na manutencao de cargo"
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Conferido 15/09/2026: NAO feita, mas falta so o front. Coluna tblcargo.comissaocaixa existe, Cargo.php tem fillable/cast e CargoService::update faz fill(). O form (pessoas/src/components/cargo/DialogCargo.vue) so tem cargo, salario e adicional — falta o campo comissaocaixa. Hoje o valor so aparece no relatorio ComissaoCaixas.vue.
<!-- SECTION:NOTES:END -->

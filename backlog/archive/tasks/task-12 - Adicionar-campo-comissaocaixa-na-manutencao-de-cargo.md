---
id: TASK-12
title: Adicionar campo comissaocaixa na manutencao de cargo
status: To Do
assignee: []
created_date: '2026-09-12 15:53'
updated_date: '2026-10-10 19:06'
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

Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): juntada na TASK-89. O RH novo paga o caixa pela rubrica Caixa (codrubrica 5, 135 lançamentos, último em 26/09, 10/15/20%) e nunca lê tblcargo.comissaocaixa; só as Metas antigas leem (TASK-7 remove).
<!-- SECTION:NOTES:END -->

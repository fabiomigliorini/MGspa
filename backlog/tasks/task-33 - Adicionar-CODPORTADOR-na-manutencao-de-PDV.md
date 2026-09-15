---
id: TASK-33
title: Adicionar CODPORTADOR na manutencao de PDV
status: To Do
assignee: []
created_date: '2026-09-12 15:53'
updated_date: '2026-09-15 14:46'
labels:
  - negocios
dependencies: []
priority: high
type: feature
ordinal: 4000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: negocios/todo — secao EM ANDAMENTO
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Conferido 15/09/2026: PARCIAL. Backend pronto (coluna tblpdv.codportador, fillable/relacao em Pdv.php, PdvService::update faz fill). Falta o campo no DialogEditarPdv.vue (so edita apelido/filial/setor/observacoes); 0 de 311 PDVs tem codportador; nada le $pdv->codportador (Pix usa portador do request com fallback BB). No lugar, PadraoPage.vue escolhe portador salvo LOCALMENTE no dispositivo. TASK-34 depende desta.
<!-- SECTION:NOTES:END -->

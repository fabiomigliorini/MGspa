---
id: TASK-82
title: Biblioteca de imagens com lixeira
status: To Do
assignee: []
created_date: '2026-09-12 15:56'
updated_date: '2026-10-10 19:06'
labels:
  - estoque
dependencies: []
priority: low
type: feature
ordinal: 85000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: estoque/docs/BLUEPRINT_MIGRACAO_MGLARA.md — Fase C.7. O upload no produto ja existe (v1/imagem); falta a tela de biblioteca global e a lixeira/esvaziar.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): mandar para a lixeira já existe (POST imagem/{id}/inativo; 9.149 imagens inativas). Faltam listagem, restaurar, esvaziar e telas. O apiResource('imagem') e o DELETE imagem/{id}/inativo apontam para index/show/destroy/ativar, que não existem no ImagemController (só store, update e inativar): dão erro se chamados.
<!-- SECTION:NOTES:END -->

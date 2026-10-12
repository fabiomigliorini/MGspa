---
id: TASK-67
title: 'Portador: remover o WHERE provisorio apos corrigir os dados da tabela'
status: To Do
assignee: []
created_date: '2026-09-12 15:54'
updated_date: '2026-10-10 19:06'
labels:
  - api
dependencies: []
priority: low
type: feature
ordinal: 71000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: marcacao no codigo — api/app/Mg/Portador/PortadorService.php:391: "//TODO Where provisório porque tem uns valores errados na tabela. Ex ano que começa com 00"
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): arquivada. O WHERE não filtra nada no dev (tblextratobancario sem ano < 2000); o extrato bancário precisa ser refeito inteiro.
<!-- SECTION:NOTES:END -->

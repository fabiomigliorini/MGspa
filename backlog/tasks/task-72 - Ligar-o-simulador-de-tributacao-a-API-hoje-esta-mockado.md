---
id: TASK-72
title: Ligar o simulador de tributacao a API (hoje esta mockado)
status: To Do
assignee: []
created_date: '2026-09-12 15:54'
updated_date: '2026-10-10 19:07'
labels:
  - notas
dependencies: []
priority: medium
type: feature
ordinal: 75000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: marcacao no codigo — notas/src/components/drawers/TributacaoSimuladorDrawer.vue:45, :52 e :69. Alem dos TODOs de 'Buscar da API' e 'Chamar API de calculo', as linhas 88/102/116 usam textos fixos 'Lei Complementar nº XXX/2024' — o drawer inteiro e mock.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): o motor já existe desde dez/2025: TributacaoService::simular() (api/app/Mg/Tributacao/TributacaoService.php:223) resolve as regras e calcula sem gravar. Faltam rota, controller e ligar o drawer (TributacaoSimuladorDrawer.vue :45, :52, :69 e os textos 'Lei Complementar nº XXX').
<!-- SECTION:NOTES:END -->

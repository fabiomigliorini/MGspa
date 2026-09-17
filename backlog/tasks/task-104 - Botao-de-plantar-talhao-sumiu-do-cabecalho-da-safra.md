---
id: TASK-104
title: Botao de plantar talhao sumiu do cabecalho da safra
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-09-16 21:30'
updated_date: '2026-09-16 21:31'
labels:
  - agro
dependencies: []
priority: high
type: bug
ordinal: 103000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Regressao introduzida em 426a1723 [FIX] Padrao por Talhao (22/07/2026): o commit trocou o agrupamento padrao para 'talhao' e, no mesmo hunk, apagou o q-btn 'Plantar talhao' que ficava ao lado do q-btn-toggle no cabecalho de 'Plantios por fazenda'. Sobrou so o + de dentro do card de cada fazenda. Consequencia: numa safra sem nenhum plantio nenhum card e renderizado, entao nao existe + em lugar nenhum e e impossivel criar o primeiro plantio pela interface. O MgEmptyState ainda manda 'Use + para plantar o primeiro', apontando pra um botao que nao existe. novoPlantio() e o PlantioWizardDialog continuam intactos — so o gatilho sumiu. Correcao: restaurar o botao no cabecalho da secao.
<!-- SECTION:DESCRIPTION:END -->

---
id: TASK-103
title: Sync do agro nao remove do cache offline os cadastros apagados no servidor
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-16 21:14'
updated_date: '2026-09-22 13:54'
labels:
  - agro
dependencies: []
priority: medium
type: bug
ordinal: 102000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: TASK-101, 16/09/2026 — o PlantioMapaDialog mostrou 4 safras e plantios (Rapador / Cascalho, Seco, Mato) que nao existem no api-dev (1 safra, 37 plantios). Causa: sincronizacao.puxarTabela/puxarPlantios so fazem bulkPut no Dexie; linha apagada (ou de outro ambiente) fica no cache pra sempre e aparece em selects, mapa e listagens. Correcao: apos baixar todas as paginas com sucesso, apagar as linhas cujo 'sincronizado' e anterior ao timestamp do pull (plantio: so das safras ativas puxadas). Workaround ate la: limpar os dados do site no navegador.
<!-- SECTION:DESCRIPTION:END -->

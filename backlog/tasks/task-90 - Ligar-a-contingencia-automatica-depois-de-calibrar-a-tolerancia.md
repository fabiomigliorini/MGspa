---
id: TASK-90
title: Ligar a contingencia automatica depois de calibrar a tolerancia
status: To Do
assignee: []
created_date: '2026-09-12 15:56'
updated_date: '2026-09-15 15:11'
labels:
  - api
dependencies: []
priority: medium
type: feature
ordinal: 14000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: api/database/NFE.md — "Contingência automática — ainda não ligada". Nasce false de proposito. A latencia observada e de 164 a 376 ms contra tolerancia de 15 s, folga grande demais para calibrar. Esperar a tbsefazcomunicacao registrar um periodo RUIM de SEFAZ e usar esse numero. Gatilho em app/Mg/NFePHP/ContingenciaService.php. Ligar empresa por empresa na tela da Empresa (app pessoas). DEPENDE de conferir o QR Code 3.0 antes.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Conferido 15/09/2026: PARCIAL. tblempresa: Migliorini (1) ja esta com contingenciaautomatica=true e tolerancia 20s; FDF, Sinopel, Fazenda, Arquitetura, Terceiros e Particular seguem false com 15s. Tela pessoas/src/components/empresa/CardContingencia.vue tem o toggle. Falta decidir/ligar nas demais empresas emissoras.
<!-- SECTION:NOTES:END -->

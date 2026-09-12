---
id: TASK-90
title: Ligar a contingencia automatica depois de calibrar a tolerancia
status: To Do
assignee: []
created_date: '2026-09-12 15:56'
labels:
  - feature
  - api
dependencies: []
ordinal: 90000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: api/database/NFE.md — "Contingência automática — ainda não ligada". Nasce false de proposito. A latencia observada e de 164 a 376 ms contra tolerancia de 15 s, folga grande demais para calibrar. Esperar a tbsefazcomunicacao registrar um periodo RUIM de SEFAZ e usar esse numero. Gatilho em app/Mg/NFePHP/ContingenciaService.php. Ligar empresa por empresa na tela da Empresa (app pessoas). DEPENDE de conferir o QR Code 3.0 antes.
<!-- SECTION:DESCRIPTION:END -->

---
id: TASK-121
title: 'Fechar negocio nao sincronizado: tentar sincronizar antes de recusar'
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-21 15:16'
updated_date: '2026-09-21 15:18'
labels:
  - negocios
dependencies: []
priority: medium
type: enhancement
ordinal: 113000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Ao fechar (e cancelar/PIX/Stone/Saurus/Comanda) um negocio com sincronizado=false, o PDV recusava direto com 'Impossivel fechar um negocio nao sincronizado com o servidor!'. Agora tenta sincronizar primeiro e so recusa se a sincronizacao falhar.
<!-- SECTION:DESCRIPTION:END -->

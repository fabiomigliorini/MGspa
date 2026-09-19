---
id: TASK-35
title: Integracao PIX Sicredi
status: Done
assignee: []
created_date: '2026-09-12 15:53'
updated_date: '2026-09-15 14:46'
labels:
  - negocios
dependencies: []
priority: high
type: feature
ordinal: 7000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: negocios/todo — secao IMPORTANTES. Conferido: o backend ja tem Mg/Pix/Sicredi/PixSicrediApiService.php e PixSicrediService.php, mas nao ha rota em api/routes/api.php nem uso no front de negocios.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Conferido 15/09/2026: implementada e em producao. PixService despacha por numerobanco 748 para PixSicrediService (transmitir/consultar/consultarPix/pdf); certificados no .env; portador 202052 'Sicredi 82676-1 MIG' (filial 101) com 23.584 cobrancas CONCLUIDAS entre 14/04 e 03/08/2026. A observacao 'nao ha rota' da descricao estava desatualizada: as rotas pix/cob/* sao genericas.
<!-- SECTION:NOTES:END -->

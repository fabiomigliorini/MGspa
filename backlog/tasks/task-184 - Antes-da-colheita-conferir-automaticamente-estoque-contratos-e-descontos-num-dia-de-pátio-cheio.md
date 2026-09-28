---
id: TASK-184
title: >-
  Antes da colheita, conferir automaticamente estoque, contratos e descontos num
  dia de pátio cheio
status: In Progress
assignee:
  - '@eduardo'
created_date: '2026-09-28 21:11'
updated_date: '2026-09-28 21:11'
labels:
  - agro
dependencies: []
priority: high
type: task
ordinal: 197000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Não existe teste nenhum do agro (sem phpunit.xml nem banco de teste; RefreshDatabase apagaria o dev) e a bateria da auditoria de 25/09 (api/tests/agro-armazenamento/) se perdeu sem commit. Bateria nova em api/tests/agro/: PHP CLI no container mgspa-api, massa isolada ZZTESTE, ações por HTTP real com curl_multi, camadas de cenários, corrida, descontos, valores, listagem e estresse (aparelhos virtuais: 3 no pico real, rampa até 24), mais conferencia.sql só leitura para rodar na PROD. Plano: /home/usuario/.claude/plans/valide-valores-saldos-e-merry-pnueli.md (Fases 1 e 10).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 A bateria está versionada, documentada e roda com um comando
- [ ] #2 Um dia com 3 aparelhos roda sem erro e sem número divergente
- [ ] #3 O limite do servidor foi medido
- [ ] #4 A conferência só-leitura foi rodada na PROD antes e depois da publicação
<!-- AC:END -->

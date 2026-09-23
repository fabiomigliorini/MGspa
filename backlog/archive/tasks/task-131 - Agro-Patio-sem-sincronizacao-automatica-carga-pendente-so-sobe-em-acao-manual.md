---
id: TASK-131
title: >-
  Agro/Patio: sem sincronizacao automatica, carga pendente so sobe em acao
  manual
status: To Do
assignee: []
created_date: '2026-09-21 21:33'
updated_date: '2026-09-23 21:04'
labels:
  - agro
dependencies: []
priority: medium
type: bug
ordinal: 142000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
A sincronizacao so roda em dois momentos: onMounted da CargaPage e o botao Sincronizar. Nao ha setInterval, nao ha listener de window 'online' (o unico listener online/offline do app esta no MapaTalhoes, so para as tiles do mapa) e nao ha retry ao voltar a rede. Numa balanca que fica com a tela aberta o dia inteiro, se a internet cair e voltar as cargas gravadas no Dexie continuam pendentes ate alguem recarregar a pagina ou clicar em Sincronizar - e a store de sincronizacao ja marca online=false quando da ERR_NETWORK, entao da pra religar sozinho. Propor: listener de 'online' + heartbeat (ex.: a cada 60s) chamando sincronizar() sem force, respeitando o TTL do pull pesado.
<!-- SECTION:DESCRIPTION:END -->

## Comments

<!-- COMMENTS:BEGIN -->
created: 2026-09-23 21:04
---
Dobrada na TASK-169 (regra 3 do CLAUDE.md): virou o critério de aceite #2 da task-mãe, e o texto desta descrição está preservado nas notas da TASK-169. Arquivada para sair do board — não foi descartada.
---
<!-- COMMENTS:END -->

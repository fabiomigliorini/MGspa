---
id: TASK-157
title: Usuario ainda pode esbarrar no lock da nota segurado pelo robo de pendentes
status: To Do
assignee: []
created_date: '2026-09-23 20:18'
updated_date: '2026-09-23 21:01'
labels:
  - api
dependencies: []
priority: low
type: bug
ordinal: 166000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: varredura da TASK-123 (achado U13). A TASK-149 reduziu a chance (dedup por nota + robo na fila 'low'), mas nao fechou o caso: o NFePHPResolverJob cede a vez quando ja ha operacao em andamento (NFePHPResolverJob ~:69), porem o inverso nao existe — se o robo pegou o lock primeiro e esta numa conversa lenta com a SEFAZ, o clique do operador em Transmitir/Consultar bate em NFePHPService::lock (~:62) e recebe 'Outra operacao ja esta em andamento. Tente novamente.' em vermelho. Com APP_DEBUG=false o Handler (~:1073) troca a mensagem por 'Server Error' 500, que nao diz nada ao operador.

Ideias (confirmar): fazer o lock esperar um pouco em vez de falhar na hora (Cache::lock()->block(n)); ou devolver 409 com mensagem propria, preservada mesmo com APP_DEBUG=false, e o front tratar com texto amigavel ('A nota esta sendo processada, aguarde'); ou o robo nao pegar o lock de nota emitida nos ultimos minutos.
<!-- SECTION:DESCRIPTION:END -->

## Comments

<!-- COMMENTS:BEGIN -->
created: 2026-09-23 21:01
---
Dobrada na TASK-123 (regra 3 do CLAUDE.md): virou o critério de aceite #4 da task-mãe, e o detalhe técnico desta descrição está preservado nas notas da TASK-123. Arquivada para sair do board — não foi descartada.
---
<!-- COMMENTS:END -->

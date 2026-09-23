---
id: TASK-149
title: >-
  Robo de pendentes re-enfileira todos a cada 10 min sem dedup e segura o lock
  da nota por minutos com SEFAZ lenta
status: To Do
assignee: []
created_date: '2026-09-23 15:48'
labels:
  - api
dependencies: []
priority: low
type: bug
ordinal: 158000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: varredura da TASK-123 (U12 e U13, NAO verificados — confirmar antes de mexer). (U12) app/Console/Commands/NFePHPCommandResolverPendentes.php (~:48) despacha um NFePHPResolverJob para TODOS os pendentes a cada 10 min sem checar se ja ha job na fila; com SEFAZ lenta cada job prende um worker por minutos, os 16 workers saturam e o NFePHPEnviarJob 'urgent' do PDV fica em 'Na fila...' alem do teto do front. (U13) enquanto o ResolverJob segura o lock da nota (NFePHPService.php ~:62), um reclique do operador da 'Outra operacao ja esta em andamento' em vermelho (com APP_DEBUG=false vira 'Server Error' 500 — Handler.php ~:1073) e o robo autoriza logo em seguida. Ideias: dedup por nota (Cache::add / WithoutOverlapping / ShouldBeUnique), nao re-enfileirar nota que ja tem progresso 'processando', e fila propria de prioridade menor para o robo.
<!-- SECTION:DESCRIPTION:END -->

---
id: TASK-106
title: Sync do patio engole erro e deixa cadastros velhos sem aviso
status: To Do
assignee: []
created_date: '2026-09-16 21:54'
updated_date: '2026-09-23 21:04'
labels:
  - agro
dependencies: []
priority: medium
type: bug
ordinal: 105000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Encontrado na TASK-105, 16/09/2026. CargaPage.vue e IndexPage.vue chamam store.sincronizar().catch(() => {}). sincronizacao.sincronizar() relanca todo erro que nao seja ERR_NETWORK (500, 404, erro de Dexie como chave invalida). Como carga.sincronizar() faz await sincronizacao.sincronizar() ANTES de carregarReferencias(), qualquer falha no meio do pull aborta o recarregamento: a store fica com os cadastros do mount (possivelmente vazios), o ultimaSincronizacao nunca e gravado (entao falha de novo a cada abertura) e o operador nao ve nada — nem toast, nem icone. Nao foi a causa do SILO sumido (era o TTL), mas produz exatamente o mesmo sintoma e e indistinguivel dele pela tela. Sugestao: notificar o erro (notifyError) e recarregar as referencias do Dexie mesmo quando o pull falha (o que ja foi gravado continua valido).
<!-- SECTION:DESCRIPTION:END -->

## Comments

<!-- COMMENTS:BEGIN -->
created: 2026-09-23 21:04
---
Dobrada na TASK-169 (regra 3 do CLAUDE.md): virou o critério de aceite #1 da task-mãe, e o texto desta descrição está preservado nas notas da TASK-169. Arquivada para sair do board — não foi descartada.
---
<!-- COMMENTS:END -->

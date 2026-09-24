---
id: TASK-152
title: >-
  fechar() deixa a flag 'fechando' presa quando o negocio nao e editavel (return
  dentro do try sem finally)
status: Done
assignee: []
created_date: '2026-09-23 15:48'
updated_date: '2026-09-23 16:34'
labels:
  - negocios
dependencies: []
priority: low
type: bug
ordinal: 162000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: varredura da TASK-123 (U17), confirmado na leitura. negocios/src/pages/IndexPage.vue ~:190: quando !sNegocio.podeEditar o fechar() faz 'return' de dentro do try e 'fechando = false' (fora do try, depois do catch) nunca roda. A partir dai todo F3/fechar nessa instancia da IndexPage cai em 'Duplo fechamento detectado, abortando!'. Workaround: trocar de negocio (router-view :key remonta a pagina). Fix: mover 'fechando = false' para um finally.
<!-- SECTION:DESCRIPTION:END -->

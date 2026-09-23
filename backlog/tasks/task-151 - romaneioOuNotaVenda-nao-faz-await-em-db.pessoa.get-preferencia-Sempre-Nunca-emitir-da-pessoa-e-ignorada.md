---
id: TASK-151
title: >-
  romaneioOuNotaVenda nao faz await em db.pessoa.get(): preferencia
  'Sempre/Nunca emitir' da pessoa e ignorada
status: Done
assignee: []
created_date: '2026-09-23 15:48'
updated_date: '2026-09-23 16:33'
labels:
  - negocios
dependencies: []
priority: medium
type: bug
ordinal: 160000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: varredura da TASK-123 (U16), confirmado na leitura. negocios/src/pages/IndexPage.vue ~:419 'const p = db.pessoa.get(sNegocio.negocio.codpessoa)' sem await: p e uma Promise, p.notafiscal e sempre undefined e o switch cai no default. Cliente cadastrado com notafiscal=1 (Sempre emitir NF-e 55) recebe o fluxo padrao (NFC-e 65) e com 9 (Nunca) idem. Fix: 'const p = await db.pessoa.get(...)' e tratar p undefined (pessoa nao esta no Dexie) caindo no padrao.
<!-- SECTION:DESCRIPTION:END -->

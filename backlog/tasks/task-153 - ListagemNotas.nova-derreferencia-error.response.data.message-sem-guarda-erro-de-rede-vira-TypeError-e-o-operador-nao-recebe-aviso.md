---
id: TASK-153
title: >-
  ListagemNotas.nova() derreferencia error.response.data.message sem guarda:
  erro de rede vira TypeError e o operador nao recebe aviso
status: Done
assignee: []
created_date: '2026-09-23 15:48'
updated_date: '2026-09-23 16:34'
labels:
  - negocios
dependencies: []
priority: low
type: bug
ordinal: 163000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: varredura da TASK-123 (U18), confirmado na leitura. negocios/src/components/offline/ListagemNotas.vue ~:84: no catch do POST /v1/pdv/negocio/{cod}/nota-fiscal, 'error.response.data.message' lanca TypeError quando nao ha response (timeout 15s do axios, rede fora); o TypeError sai do catch, novaNota() rejeita sem tratamento e nenhum Notify aparece. Fix: usar o padrao do projeto (error.response?.data?.message || error.message).
<!-- SECTION:DESCRIPTION:END -->

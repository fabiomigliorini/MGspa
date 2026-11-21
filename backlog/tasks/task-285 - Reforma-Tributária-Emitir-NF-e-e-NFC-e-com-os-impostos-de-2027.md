---
id: TASK-285
title: 'Reforma Tributária: Emitir NF-e e NFC-e com os impostos de 2027'
status: To Do
assignee: []
created_date: '2026-11-19 16:34'
labels:
  - notas
dependencies:
  - TASK-284
priority: high
type: feature
ordinal: 296000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
**Problema:** a nota calcula o imposto quando é criada, e a tabela de PIS/COFINS não tem data de vigência. Uma nota criada em dezembro e transmitida em janeiro sairia com PIS/COFINS e sem a CBS cheia. Além disso, a nota técnica do IBS/CBS (NT 2025.002) continua mudando de versão, e a biblioteca que monta o XML precisa acompanhar para a nota não ser rejeitada.

**Escopo:** NF-e e NFC-e de 2027 com as regras novas, recálculo na transmissão quando a nota muda de ano, PIS/COFINS fora a partir de 01/01/2027 e biblioteca do XML na versão da nota técnica em vigor.

**Prazo:** 31/12/2026.

Coordenação: decisões, premissas e informações técnicas no doc-7 (Reforma Tributária: decisões, premissas e informações técnicas). Ao fechar, registrar lá o que foi decidido.
<!-- SECTION:DESCRIPTION:END -->

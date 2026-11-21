---
id: TASK-289
title: >-
  Reforma Tributária: Recalcular os preços de venda com IBS/CBS e reajustar em
  lote no go-live
status: To Do
assignee: []
created_date: '2026-11-19 16:35'
labels:
  - estoque
dependencies:
  - TASK-284
  - TASK-288
priority: low
type: feature
ordinal: 300000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
**Problema:** hoje o preço de venda embute 3,65% de PIS/COFINS sem crédito. A partir de 2027 passa a ter cerca de 11,3% de CBS/IBS por fora, com crédito da compra. O preço que mantém a mesma margem muda de produto para produto. Hoje não existe regra de preço com IBS/CBS nem jeito de reajustar vários produtos de uma vez, e são 43 mil produtos ativos.

**Escopo:** regra de preço a partir do custo sem crédito, tela de reajuste de preço em lote e reprecificação no go-live. As etiquetas saem pela tela de etiquetas que já existe.

**Prazo:** go-live, por último.

Coordenação: decisões, premissas e informações técnicas no doc-7 (Reforma Tributária: decisões, premissas e informações técnicas). Ao fechar, registrar lá o que foi decidido.
<!-- SECTION:DESCRIPTION:END -->

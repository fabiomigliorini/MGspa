---
id: TASK-288
title: >-
  Reforma Tributária: Descontar do custo da compra o crédito de IBS/CBS da nota
  do fornecedor
status: To Do
assignee: []
created_date: '2026-11-19 16:35'
labels:
  - notas
dependencies:
  - TASK-284
priority: medium
type: feature
ordinal: 299000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
**Problema:** a partir de 2027 a compra passa a gerar crédito de IBS/CBS, e o valor muda conforme o fornecedor (o do Simples gera menos). Hoje o custo do estoque é gravado com o valor cheio da nota, sem tirar nenhum imposto recuperável. O custo fica inflado pelo crédito, e isso contamina a margem, os relatórios e a sugestão de preço.

**Escopo:** ler o IBS/CBS da nota de entrada, guardar o crédito por item e gravar o custo já sem o crédito.

Coordenação: decisões, premissas e informações técnicas no doc-7 (Reforma Tributária: decisões, premissas e informações técnicas). Ao fechar, registrar lá o que foi decidido.
<!-- SECTION:DESCRIPTION:END -->

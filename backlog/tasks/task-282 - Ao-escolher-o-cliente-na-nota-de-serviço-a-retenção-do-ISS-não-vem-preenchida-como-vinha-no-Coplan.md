---
id: TASK-282
title: >-
  Ao escolher o cliente na nota de serviço, a retenção do ISS não vem preenchida
  como vinha no Coplan
status: To Do
assignee: []
created_date: '2026-11-18 15:12'
labels:
  - notas
dependencies: []
priority: low
type: feature
ordinal: 293000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
No Coplan (sistema da prefeitura de Sinop), ao escolher o cliente a nota já dizia se havia retenção do ISS: a prefeitura tem o cadastro de quem é substituto tributário. No emissor nacional (TASK-279) a retenção é escolhida à mão em cada nota.

O Sistema Nacional não substitui isso (verificado em 18/11/2026, consultas só de leitura ao ADN): a parametrização de retenções do município (GET /parametrizacao/5107909/{competencia}/retencoes) responde 'Parâmetros de retenções do Artigo Sexto não encontrados' para Sinop, em produção e homologação; e, onde existe, ela é por serviço, não por cliente. O ADN informa a alíquota municipal por serviço (GET /parametrizacao/5107909/17.03.01.000/{competencia}/aliquota = 4,00% desde 30/10/2024; o código vai com pontos, 9 dígitos).

Pedido do Fábio: marcar no cadastro do cliente se ele retém o ISS, e a nota pré-selecionar a retenção ao escolher o tomador (MgNotaFiscalServicoForm, campo 'Retenção do ISS'; com retenção o tomador já é obrigatório e a alíquota fica habilitada).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Cadastro do cliente (pessoas) com a marcação de que ele retém o ISS (substituto tributário)
- [ ] #2 Nota de serviço pré-seleciona a retenção do ISS ao escolher o tomador
- [ ] #3 Definido com o Fábio se a retenção pré-selecionada pode ser trocada na nota
<!-- AC:END -->

---
id: TASK-280
title: >-
  Toda nota de serviço da FDF pede para digitar de novo as alíquotas do Simples
  do mês
status: To Do
assignee: []
created_date: '2026-11-18 10:17'
labels:
  - notas
dependencies: []
priority: low
type: feature
ordinal: 291000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Hoje cada nota de serviço (TASK-279) pede dois percentuais que mudam todo mês com o faturamento dos últimos 12 meses do Simples, iguais em todas as notas do mês:
- alíquota efetiva do ISS no Simples (issaliquota; só vai na DPS com ISS retido — E0625);
- % aproximado total dos tributos do Simples (tributospercentual / pTotTribSN; obrigatório para ME/EPP — E0712).

Pedido do Fábio (18/11/2026): informar esses percentuais uma vez por mês num cadastro da filial e a nota puxar automaticamente, pela filial e pelo mês da competência.

Onde mexe: cadastro da filial no pessoas (lista de meses com os percentuais), api (tabela por filial e mês + endpoint), e o formulário da nota de serviço no notas (MgNotaFiscalServicoForm / notaFiscalServicoStore), que hoje começa com os dois campos vazios.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Cadastro na filial (pessoas) dos percentuais por mês: alíquota do ISS no Simples e % aproximado dos tributos do Simples
- [ ] #2 Nota de serviço preenche os dois percentuais sozinha pela filial e pelo mês da competência (inclusive ao trocar filial ou competência)
- [ ] #3 Definido com o Fábio o que acontece quando o mês da competência não tem percentual cadastrado e se a nota deixa alterar o valor puxado
<!-- AC:END -->

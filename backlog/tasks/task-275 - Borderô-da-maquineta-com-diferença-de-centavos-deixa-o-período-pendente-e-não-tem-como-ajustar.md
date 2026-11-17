---
id: TASK-275
title: >-
  Borderô da maquineta com diferença de centavos deixa o período pendente e não
  tem como ajustar
status: To Do
assignee: []
created_date: '2026-11-16 19:55'
labels:
  - contas
dependencies: []
priority: high
type: feature
ordinal: 288000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido de 16/11/2026: pensar em como fazer um ajuste na maquineta e/ou ter uma tolerância mínima.

Hoje a conferência do borderô só fecha se a quantidade e o total baterem no centavo (MaquinetaLoteService::conferir, api/app/Mg/Maquineta/MaquinetaLoteService.php:278). Qualquer diferença deixa o período pendente, e o único caminho é corrigir os lançamentos ou o digitado.

O caixa em espécie já tem as duas coisas: tolerância por portador (tblportador.tolerancia, padrão R$ 2,00: fecha e registra a diferença) e ajuste como movimento do período (tblportadormovimento tipo A). Relacionada: TASK-223 (movimento do período da maquineta), onde um ajuste moraria.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Decidido: ajuste no período da maquineta, tolerância mínima ou os dois
- [ ] #2 Período com diferença pequena no borderô consegue ser conferido, com a diferença registrada
<!-- AC:END -->

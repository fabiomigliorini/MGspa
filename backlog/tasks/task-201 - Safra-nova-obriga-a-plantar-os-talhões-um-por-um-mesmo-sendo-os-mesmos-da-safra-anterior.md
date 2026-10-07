---
id: TASK-201
title: >-
  Safra nova obriga a plantar os talhões um por um, mesmo sendo os mesmos da
  safra anterior
status: To Do
assignee: []
created_date: '2026-10-07 18:37'
labels:
  - agro
dependencies: []
priority: low
type: feature
ordinal: 214000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido do usuário (07/10/2026): ao abrir uma safra nova, poder reaproveitar o layout de talhões de outra safra/fazenda. Na prática a fazenda quase não muda — troca a safra, os talhões são os mesmos — e hoje cada talhão tem que ser plantado de novo à mão na safra nova.

Como está hoje (api/app/Mg/Fazenda):
- Talhao é permanente da fazenda (talhao, area, geometria, cor, lat/long).
- Plantio liga Talhao a uma Safra (codsafra, codfazenda, codtalhao) e carrega a própria cópia de talhao, geometria, cor, areaplantada, além do que é da safra: codvariedade, dataplantio, expectativasacas, hacolhido.
- Reaproveitar o layout = criar na safra nova um Plantio para cada talhão plantado na safra de origem, copiando o que é do talhão (nome, geometria, cor, área) e deixando em branco o que é da safra (variedade, data de plantio, expectativa, colhido).

A decidir com quem prioriza: se a origem pode ser de cultura diferente (soja → milho), se a cópia é por fazenda ou da safra inteira, e se é ação na criação da safra ou botão na safra já criada (filosofia: um botão, uma ação; nada automático sem pedido).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Na safra nova dá para escolher uma safra de origem e trazer os talhões dela de uma vez, por fazenda
- [ ] #2 Vêm nome, desenho no mapa, cor e área; variedade, data de plantio, expectativa e colhido ficam em branco para preencher
- [ ] #3 Talhão já plantado na safra nova não é duplicado
<!-- AC:END -->

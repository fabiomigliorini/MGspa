---
id: TASK-274
title: "Na troca de natureza do negócio, a Loja destino não tem como ser apagada"
status: Done
assignee:
  - "@eduardo"
created_date: "2026-11-11 18:39"
updated_date: "2026-11-11 18:40"
labels:
  - negocios
dependencies: []
priority: medium
type: bug
ordinal: 286000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->

Pedido de 11/11/2026. No PDV (negocios), na janela de trocar a natureza do negócio, a Loja destino aparece quando a natureza movimenta o destino (transferência, venda com retirada em outra loja). Escolhida uma loja, não tem como apagar: o campo não tem o X de limpar, e trocar a natureza mantém a loja escolhida. Quem quer vender para um cliente fica preso à loja destino. Local de Estoque e Natureza de Operação continuam obrigatórios; a Loja destino só é obrigatória na transferência (já é assim).

<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria

<!-- AC:BEGIN -->

- [ ] #1 A Loja destino tem o X para apagar o valor
- [ ] #2 Trocar a natureza na janela deixa a Loja destino em branco
- [ ] #3 Local de Estoque e Natureza continuam obrigatórios; a Loja destino só na transferência
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->

Correção (11/11/2026), na árvore, sem commit. negocios/src/components/selects/SelectEstoqueLocal.vue passa a montar no @components/MgSelect (é ele que dá o X de limpar fora do Tab; os outros usos, entrega e ligar requisição, ficam iguais, porque já passavam outlined). negocios/src/components/offline/DetalheNegocio.vue: a Loja destino ganha clearable, e o watch da natureza apaga a loja destino quando a natureza escolhida é diferente da do negócio. Abrir a janela não apaga nada. As regras já existiam: Local e Natureza obrigatórios; Loja destino só na transferência, e diferente do local. Ao salvar, o informarNatureza já grava destino nulo quando a natureza não movimenta destino.

<!-- SECTION:NOTES:END -->

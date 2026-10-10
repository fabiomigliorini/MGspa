---
id: TASK-52
title: 'Menu do negócios ainda mostra a Conferência antiga, que não confere nada'
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-12 15:53'
updated_date: '2026-10-10 17:28'
labels:
  - negocios
dependencies: []
priority: low
type: chore
ordinal: 130000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: negocios/todo — secao DESEJAVEIS (pedido original: adicionar totais no /conferencia).

O item Administração → Conferência (/conferencia) do negocios é o protótipo antigo de conferência por PDV/dia. A conferência de verdade ficou com a tela Fechamentos do contas (v1/conferencia, Mg\Conferencia — doc-3, que já previa: "O protótipo negocios /conferencia sai quando a tela Fechamentos cobrir"). Os totais pedidos aqui ficam por conta dela.

O protótipo ainda estava quebrado: a tela lia valorstone e o backend devolvia valorpagarme (coluna Stone sempre vazia).

Removido:
- negocios: ConferenciaPage.vue, ConferenciaLayout.vue, drawers/ConferenciaLeftDrawer.vue, stores/conferencia.js, item do MainLayout e rota /conferencia.
- api: rota GET v1/pdv/negocio/conferencia, PdvController::conferencia e PdvService::conferencia.

Não mexe em Mg\Conferencia (contas), estoque-saldo-conferencia, conferência de NFe de terceiro nem na Conferência das Confissões de Dívida.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Item Conferência e rota /conferencia fora do app negocios (página, layout, drawer e store apagados)
- [x] #2 Endpoint GET v1/pdv/negocio/conferencia, PdvController::conferencia e PdvService::conferencia fora da api
<!-- AC:END -->

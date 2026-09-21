---
id: TASK-119
title: 'Wizard Receber: Dinheiro pedir 2o Enter para lancar (igual as demais formas)'
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-21 15:06'
updated_date: '2026-09-21 15:11'
labels:
  - negocios
dependencies: []
priority: medium
type: enhancement
ordinal: 118000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: pedido do Fabio (2026-09-21).

Hoje no wizard de recebimento (negocios/src/components/offline/ReceberDialog.vue), nas formas cartao/PIX/etc o operador altera o valor, o 1o Enter aplica a edicao (volta pro modo texto) e o 2o Enter continua. Em Dinheiro o 1o Enter no valor ja lanca e finaliza (aplicarEdicao() chama dinheiro() quando formaAtual.troco), sem chance de conferir.

Objetivo: padronizar - em Dinheiro o 1o Enter aplica o valor (mostra valor recebido + troco em modo texto) e so o 2o Enter lanca, dando oportunidade de corrigir (Insert edita de novo).

Pontos a ajustar:
- aplicarEdicao(): remover o atalho que lanca direto no Dinheiro
- cancelarEdicao(): Dinheiro passa a ter modo texto; Esc na edicao volta ao texto (se ja houver valor) em vez de voltar pra escolha da forma
- hint do input ('Enter lanca · Esc volta') e rotulo do botao do rodape coerentes com o novo fluxo
- prepararValor(): Dinheiro continua entrando com o campo focado e vazio

Criterio de aceite: Dinheiro -> digita valor -> Enter mostra recebido/troco sem lancar -> Enter lanca; Insert permite corrigir antes.
<!-- SECTION:DESCRIPTION:END -->

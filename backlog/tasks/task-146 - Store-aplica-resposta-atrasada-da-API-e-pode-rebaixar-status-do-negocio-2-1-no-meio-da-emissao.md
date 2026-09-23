---
id: TASK-146
title: >-
  Store aplica resposta atrasada da API e pode rebaixar status do negocio (2->1)
  no meio da emissao
status: Done
assignee: []
created_date: '2026-09-23 15:48'
updated_date: '2026-09-23 16:40'
labels:
  - negocios
dependencies: []
priority: low
type: bug
ordinal: 155000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: varredura da TASK-123 (achado U03, 2/3 verificadores). negocios/src/stores/negocio.js: sincronizar() (~1147-1154) e atualizarNegocioPeloObjeto() (~1205-1207) aplicam o retorno do servidor sem checar se ele e mais antigo que o estado atual. Uma resposta reordenada (PUT do sincronizar, GET do recarregarDaApi disparado pelo polling PIX/PagarMe/Saurus) com codnegociostatus 1 reverte a tela para 'aberto' e desmonta ListagemNotas; um GET obsoleto com status 2 mas sem a nota nova tira o card da nota. Na pratica exige uma resposta atrasar mais que 3-4 round-trips, entao e raro; o refutador achou implausivel. Fix barato: nunca aplicar retorno cujo codnegociostatus seja menor que o atual do mesmo uuid; em sincronizar() sair cedo se o negocio local ja nao esta com status 1.
<!-- SECTION:DESCRIPTION:END -->

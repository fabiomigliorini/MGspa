---
id: TASK-161
title: >-
  Guard de resposta atrasada nao protegia o IndexedDB e bloqueava recarga manual
  em silencio
status: Done
assignee: []
created_date: '2026-09-23 20:35'
updated_date: '2026-09-23 20:35'
labels:
  - negocios
dependencies: []
priority: medium
type: bug
ordinal: 170000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: revisao dos commits da TASK-146 (achados M1 e M2 do revisor). Corrigido no mesmo dia.

(M1) A guarda so cobria this.negocio; o db.negocio.update do sincronizar() continuava gravando codnegociostatus sem checar. Como atualizarListagem() le o Dexie pelo indice [codnegociostatus+codpdv] e recarregar() copia do Dexie para a tela (e e chamado por itemAdicionar, informarNatureza, informarVendedor, exclusao de pagamento e pelo listener multi-aba), o negocio fechado reaparecia na gaveta de abertos e podia voltar para a tela — o bug da TASK-146 por outra porta. A comparacao tambem passou a usar o estado mais fresco (this.negocio quando e o negocio da tela), ja que o objeto lido do Dexie no inicio do sincronizar pode ser anterior ao F3.

(M2) atualizarNegocioPeloObjeto descartava o objeto INTEIRO e mesmo assim recarregarDaApi devolvia true: o botao 'Recarregar do servidor' do DetalheNegocio (que pergunta 'pode apagar o que eu alterei?') anunciava 'Dados Recarregados do Servidor!' sem ter recarregado nada. Pior: a guarda nao deveria valer ali — um GET explicito e recem-pedido pelo operador nao e resposta atrasada.

Corrigido: atualizarNegocioPeloObjeto e recarregarDaApi ganharam o parametro forcar e devolvem se aplicaram. Passam forcar: os carregamentos de tela (carregarPeloUuid/carregarPeloCodnegocio), o apropriar e a recarga manual. Ficam protegidos os caminhos de fundo: PUT do sincronizar e os polls de PIX/PagarMe/Saurus/Woo.
<!-- SECTION:DESCRIPTION:END -->

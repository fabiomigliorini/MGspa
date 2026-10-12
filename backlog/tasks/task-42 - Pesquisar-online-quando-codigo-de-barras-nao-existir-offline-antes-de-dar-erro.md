---
id: TASK-42
title: >-
  Pesquisar online quando codigo de barras nao existir offline, antes de dar
  erro
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-12 15:53'
updated_date: '2026-10-10 17:15'
labels:
  - negocios
dependencies: []
priority: high
type: feature
ordinal: 63000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: negocios/todo — secao IMPORTANTES
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Código fora da base offline é procurado no servidor antes de avisar 'Não encontrei' (venda, prancheta e quiosque)
- [x] #2 Quantidade N* vale para a leitura em que foi digitada, mesmo enquanto a busca online demora
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Causa: produtoStore.buscarBarras (negocios/src/stores/produto.js) disparava sincroniza() sem await, consultava o IndexedDB e devolvia vazio; a resposta do backend so gravava o produto depois, por isso entrava so na segunda leitura.

Correcao: buscarBarras guarda a promessa da consulta online; achou offline devolve na hora (online segue em segundo plano atualizando o IndexedDB); nao achou, devolve o que o backend respondeu. sincroniza() devolve data ou [] e usa timeout de 3s (padrao do axios e 15s). Sem rede ou API fora: erro imediato; backend pendurado: erro em ate 3s. Bonus: formato codigo-quantidade (012345-12) passa a funcionar via backend (ProdutoService::buscaPorBarras).

InputBarras.vue: quantidade N* lida e zerada antes do await (antes era depois); se o codigo nao for achado, o multiplicador e consumido.

Teste: apagar produto do IndexedDB (tabela produto) e ler o codigo -> entra na primeira leitura; DevTools offline -> erro na hora; latencia 10s -> erro em ~3s, outro item da base entra na hora; 3* + codigo fora da base -> entra com 3; Prancheta Adicionar Produto e Quiosque.
<!-- SECTION:NOTES:END -->

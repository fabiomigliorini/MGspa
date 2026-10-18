---
id: TASK-241
title: >-
  Listagem do Negócios fica repetindo a busca sem parar quando a API recusa o
  pedido
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-18 13:57'
updated_date: '2026-10-18 14:19'
labels:
  - negocios
dependencies: []
priority: low
type: bug
ordinal: 250000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Quando a API recusa a busca da tela Listagem do Negócios, a tela fica pedindo de novo sem parar e empilha um aviso vermelho a cada tentativa. No teste de 18/10/2026 (TASK-237), com um PDV que a API não reconhece, foram 143 chamadas a `/v1/pdv/negocio` em 20 segundos, todas 422. Pela leitura do código, qualquer falha cai no mesmo caminho: 500, timeout, ou o PDV sem internet.

Causa: `getNegociosPaginacao` (negocios/src/stores/listagem.js) devolve false no catch sem mexer na paginação, que fica em current_page 0 e last_page 99999. O `onLoad` da ListagemPage.vue compara as duas, conclui que ainda há página e chama `done(false)`. Com a lista vazia, o q-infinite-scroll dispara o load de novo na hora.

Referência na casa: o `getPedidosPaginacao` do store woo para a paginação no erro (current_page 9999, last_page 0). As telas de vales param porque o carregar devolve "não tem mais" quando falha.

Como reproduzir: abrir /listagem com o DevTools em Offline e mudar um filtro, ou usar um PDV cujo uuid a API não conhece. A aba Network mostra a mesma chamada repetindo.

Já existia antes da TASK-237: o build do commit anterior repete do mesmo jeito.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Quando a busca falha, a Listagem mostra o erro uma vez e para de pedir
- [x] #2 Mudar um filtro depois da falha busca de novo a partir da primeira página
- [x] #3 Abrir a Listagem faz uma busca só (com erro, um aviso só)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Correção (negocios/src/stores/listagem.js):
- catch do `getNegociosPaginacao`: `paginacao.last_page = paginacao.current_page`. O `onLoad` da ListagemPage vê que não tem mais página e chama `done(true)`, então o q-infinite-scroll para. Mudar um filtro chama `inicializa` → `getNegocios` (zera para 0/99999) + `reset()`/`resume()` do scroll, e a busca recomeça da 1ª página.
- `inicializaFiltro` não chama mais `getNegocios`: o watch do filtro na ListagemPage já busca (e é ele que faz reset/resume do scroll). Com as duas, abrir a tela pedia a página 1 duas vezes, e com erro saíam dois avisos.

Sem botão de tentar de novo nem retry automático: depois da falha, a lista só volta a buscar com mudança de filtro ou F5.

Teste (18/10/2026), build pwa numa cópia em /tmp + Chrome headless com a API interceptada:
- 422 em /v1/pdv/negocio: 1 chamada e 1 aviso ao abrir; mudar o filtro (# Negócio) = +1 chamada da página 1 e +1 aviso. O mesmo roteiro no HEAD anterior fez 53 chamadas em 6 s ao abrir e +46 depois do filtro.
- 200 com 5 páginas de 10: abrir pede 1 e 2; rolar carrega 3, 4 e 5 e para em "50 de 50".
<!-- SECTION:NOTES:END -->

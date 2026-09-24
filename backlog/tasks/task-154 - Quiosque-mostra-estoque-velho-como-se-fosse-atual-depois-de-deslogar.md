---
id: TASK-154
title: Quiosque mostra estoque velho como se fosse atual depois de deslogar
status: To Do
assignee: []
created_date: '2026-09-23 15:48'
updated_date: '2026-09-23 21:06'
labels:
  - negocios
dependencies: []
priority: high
type: bug
ordinal: 163000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Na consulta de precos do quiosque, o estoque so vem do detalhe rico consultado no backend enquanto ha sessao/autorizacao valida. Porem, `negocios/src/stores/quiosque.js` tambem persiste esse detalhe em `db.produtoDetalhe` (Dexie) e o recupera antes de tentar atualizar pelo backend. Assim, apos consultar um produto estando logado, sair/deslogar e consultar o mesmo codigo novamente, a tabela de estoque e exibida com os saldos antigos; a atualizacao falha e o catch mantem o cache silenciosamente. Um produto nunca consultado naquela sessao, nas mesmas condicoes, nao mostra estoque, o que torna a diferenca ainda mais enganosa.

Isso pode levar o cliente/operador a interpretar saldo desatualizado como disponibilidade atual. Definir e implementar o tratamento de estoque quando nao houver sessao/autorizacao ou quando a atualizacao do detalhe falhar: o saldo de cache nao pode parecer dado atual. A opcao adotada deve, no minimo, informar de forma claramente visivel que os valores sao cacheados/desatualizados, com a data/hora da ultima sincronizacao, e orientar que e preciso autenticar/atualizar para confirmar o estoque; avaliar tambem ocultar a tabela nesses cenarios. Ao receber com sucesso o detalhe atual do backend, remover o aviso e atualizar o timestamp/cache.

Critérios de aceite:
- Consultar logado, deslogar e repetir a consulta nao apresenta saldo cacheado como se fosse estoque atual.
- Se a decisao for manter a exibicao do cache, a tela identifica explicitamente que se trata de estoque nao confirmado e mostra quando ele foi sincronizado; nao basta o indicador temporario "atualizando".
- Sem cache e sem sessao/autorizacao, a tela deixa claro que o estoque esta indisponivel para consulta, sem confundir esse estado com saldo zero.
- Uma resposta atualizada com sucesso volta a exibir o estoque como atual e substitui o cache anterior.
- Cobrir o fluxo com teste/manual de regressao para consulta autenticada, logout e nova consulta do mesmo e de outro produto.
<!-- SECTION:DESCRIPTION:END -->

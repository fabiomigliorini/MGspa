---
id: TASK-154
title: Quiosque mostra estoque velho como se fosse atual depois de deslogar
status: To Do
assignee: []
created_date: '2026-09-23 15:48'
updated_date: '2026-10-05 15:53'
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

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Consulta de preço e sincronização do PDV funcionam sem usuário logado
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
AC #1 (hotfix 05/10/2026): na migração para a api/ o grupo v1/pdv/* ficou inteiro dentro do auth:api, e o quiosque sem login levava 401 no /detalhe. As rotas de leitura (produto, produto-count, produto/{barras}, produto/{barras}/detalhe, pessoa, pessoa-count, natureza-operacao, estoque-local, forma-pagamento, vale-modelo, GET prancheta, impressora) e o PUT dispositivo saíram do auth:api em api/routes/api.php; o controller já autoriza pelo uuid do PDV (PdvService::autoriza, 403 se não autorizado). Em negocios/src/stores/sincronizacao.js saiu a exigência de login para sincronizar. Escrita (POST pessoa, PUT prancheta, negócio) continua exigindo login.

O fix do AC #1 é paliativo: o PDV fica autorizado só pelo uuid na query string. A autenticação própria do PDV e a sincronização automática/incremental ficaram na TASK-197.

Achado no teste: a sincronização ficava parada em 0% sem fazer requisição porque dispositivo() espera navigator.geolocation.getCurrentPosition sem timeout, e o Chrome no Linux pode nunca responder mesmo com a permissão concedida. Entrou timeout de 10 s (maximumAge 10 min). O botão Sincronizar passa a ficar desabilitado desde o clique (importacao.rodando antes do dispositivo()).

Correção (Fábio): a localização é obrigatória para sincronizar. Sem ela (negada ou sem resposta em 10 s), a sincronização recusa com aviso fixo e não segue.

A janela de sincronização fechava como se tivesse terminado mesmo com erro: cada etapa engolia o próprio erro e o fim sempre fechava a janela. Agora qualquer etapa que falha marca importacao.erro e a janela fica aberta junto dos avisos (se o usuário cancelou, segue fechada).
<!-- SECTION:NOTES:END -->

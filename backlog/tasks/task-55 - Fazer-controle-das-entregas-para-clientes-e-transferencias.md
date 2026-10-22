---
id: TASK-55
title: >-
  Loja vende sem estoque e o cliente retira em outra loja com vale de papel;
  entregas e transferências sem controle
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-09-12 15:53'
updated_date: '2026-10-22 01:23'
labels:
  - negocios
dependencies: []
priority: high
type: feature
ordinal: 133000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Relato de 21/10/2026: quando a loja vende e não tem a mercadoria, o caixa entrega ao cliente um "vale" de papel para retirar em outra loja. A loja que atende não fica sabendo antes, ninguém acompanha o que foi separado, a transferência é digitada à mão depois e não há prova de que o cliente certo retirou. A reposição entre lojas também é manual (PDF de Sugestão de Transferências + negócio digitado), e nenhuma transferência ou venda tem controle de entrega (origem: negocios/todo, "Fazer controle das entregas para clientes e transferencias").

Prioridade High: acontece toda semana e o workaround é papel e digitação à mão.

Plano completo, decisões e etapas em backlog/docs/doc-6 (Plano da requisição entre lojas, entregas do negócio e impressoras). Resumo:
- Requisição autônoma (loja requisitante, loja requisitada, itens, destino da mercadoria: retira / entrega no endereço / reposição), código de retirada de 6 dígitos só do lado de quem pediu, painel por loja, impressão automática das duas vias.
- A separação é o próprio negócio de transferência do PDV (offline), ligado à requisição pelo QR, pelo nº ou pelo botão Separar; um processo idempotente da requisição calcula o atendido e grava o status (coluna).
- Entregas do negócio: tblnegocioentrega (entregue / com problema / não entregue), listas Entregas a fazer e A receber.
- codestoquelocaldestino passa a valer em toda transferência (backfill, select Loja destino no PDV, MGLara usa o campo).
- Indicadores de qualidade do estoque no detalhe do Local de Estoque.

As impressoras em tabela estão na task própria das impressoras.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Etapa 0: tblpedido, tblpedidoitem e tblnegocioprodutobarrapedidoitem removidas com o código Mg\Pedido; acertoentrega/codusuarioacertoentrega removidos
- [x] #2 Etapa 2: cadastro de Locais de Estoque no app estoque, com a impressora do local
- [x] #3 Etapa 3: requisição com cadastro, detalhe e painel por loja (status em coluna, código só do lado de quem pediu)
- [x] #4 Etapa 4: impressão das duas vias da requisição (térmica/A5), automática ao criar e por botão
- [x] #5 Etapa 5: Requisitar a partir do negócio fechado e sugestão abaixo do mínimo
- [x] #6 Etapa 6: codestoquelocaldestino preenchido (backfill), select Loja destino no PDV e MGLara usando o campo
- [ ] #7 Etapa 7: entregas do negócio (tblnegocioentrega) com as listas Entregas a fazer e A receber
- [ ] #8 Etapa 9: separação no negócio do PDV ligada à requisição (QR, nº, Separar), processo idempotente, justificar e finalizar
- [ ] #9 Etapa 10: entrega pela requisição com os códigos de retirada (ou justificativa)
- [ ] #10 Etapa 11: indicadores de qualidade do estoque no detalhe do Local de Estoque
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Etapa 6: o model Negocio do MGLara tinha EstoqueLocalDestino apontando para codestoquelocal (devolvia a origem); corrigido junto. PDV com o Dexie antigo de naturezas mostra a Loja destino pela flag transferencia até sincronizar a natureza com movimentadestino.
<!-- SECTION:NOTES:END -->

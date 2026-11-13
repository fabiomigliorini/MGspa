---
id: TASK-268
title: Pedido do Mercos chega zerado no PDV até alguém se apropriar
status: Done
assignee:
  - '@eduardo'
created_date: '2026-11-05 18:58'
updated_date: '2026-11-05 19:39'
labels:
  - negocios
dependencies: []
priority: medium
type: bug
ordinal: 276000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido feito no Mercos aparece em "Vendas Abertas Mercos" (drawer do PDV) com R$ 0,00 e abre com o total zerado. Só depois de Apropriar e mexer no negócio (ou Reimportar) o valor aparece certo.

Causa raiz: em produção os pedidos entram pelo cron do MGLara de 5 em 5 min (mercos:sincroniza → mercos:importa-pedidos → MGLara/app/Library/Mercos/MercosPedido.php::parsePedido), que cria o negócio sem PDV e nunca gravou valorprodutos nem valortotal (a linha "$n->valortotal = $ped->total" está comentada desde 11/2023). Quem calculava eram os triggers de total do tblnegocio/tblnegocioprodutobarra (fnTblNegocio_Atualiza_ValorProdutos / \_ValorTotal), que só agiam com codpdv NULL. Em 25/09/2026 o MGsis removeu os três triggers (commit 918d507, sql/drop_triggers_tblnegocio_totais.sql) e passou a calcular o total em PHP só na NF de terceiro; o importador do Mercos ficou de fora. valorprodutos e valortotal são NOT NULL DEFAULT 0, então o negócio nasce zerado.

Apropriar resolve porque o negócio fica editável no PDV e qualquer edição chama recalcularValorTotal() (negocios/src/stores/negocio.js), que soma os itens e manda o total no PUT. O Reimportar passa pelo importador da API (MercosPedidoService::parsePedido), que grava valortotal = total do Mercos.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria

<!-- AC:BEGIN -->

- [ ] #1 Importador do Mercos no MGLara grava valorprodutos e valortotal do negócio sem PDV, com a mesma conta dos triggers removidos em 25/09
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Decisão (05/11/2026): pedidos abertos já zerados em produção ficam como estão; o caixa resolve com Reimportar ou editando.

Implementado em dev em 05/11/2026, aguardando validação (AC #1 desmarcado até o OK). MGLara app/Library/Mercos/MercosPedido.php: depois de gravar os itens, parsePedido chama totalizaNegocio($n) quando o negócio não tem PDV; totalizaNegocio repete a conta de fnTblNegocio_Atualiza_ValorProdutos/\_ValorTotal (valorprodutos = soma de coalesce(valorprodutos, valortotal, 0) dos itens ativos; valortotal = valorprodutos - desconto + frete + seguro + outras + juros; valoravista = valortotal - valoraprazo). Com PDV, os totais continuam sendo do PDV, como no trigger. Frete só no cabeçalho, itens a preco_liquido e reimportação que só acrescenta item: tudo igual a antes.

Teste em dev: importaPedidoApos(now - 2 dias) pelo tinker do mglara-mglara-1 trouxe 11 pedidos reais (6862 a 6875 e 6470). Antes da correção: os 11 com valorprodutos = valortotal = 0 e itens somando de 22,57 a 3.449,79. Depois, rodando o mesmo import de novo: os 11 com valorprodutos = soma dos itens e valortotal = itens - desconto + frete (6470: 42,23 + 10,00 = 52,23); sem pedido nem item duplicado; MercosPedidoService::listagem() (drawer Vendas Abertas Mercos) devolvendo os totais.

Deploy em produção: git pull em /opt/www/MGLara (sem build, sem opcache, sem restart). Antes do pull, git log HEAD..origin/master tem que listar só o commit desta task; e crontab -l | grep -i mercos confirma que o cron é o do MGLara.

05/11/2026: commit fb3274f no MGLara, com push e git pull em produção. Revisão do push: fb3274f é exatamente o diff testado (1 arquivo, +26 linhas). O 2f3c3c0 (Fabio, 28/10: timezone dos containers por TZ + tzdata) foi no mesmo intervalo; ele só tem efeito quando o container for recriado com ./start (up -d --build) e não muda o fuso do PHP, que vem do config/app.php (America/Cuiaba). Pedidos que já estavam zerados não se corrigem sozinhos (SQL de correção passado no chat, a rodar se decidirem). Falta confirmar em produção que um pedido novo chegou com o total.
<!-- SECTION:NOTES:END -->

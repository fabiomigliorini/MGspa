---
id: TASK-55
title: >-
  Loja vende sem estoque e o cliente retira em outra loja com vale de papel;
  entregas e transferências sem controle
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-09-12 15:53'
updated_date: '2026-10-24 22:04'
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
- [x] #7 Etapa 7: entregas do negócio (tblnegocioentrega) com as listas Entregas a fazer e A receber
- [x] #8 Etapa 9: separação no negócio do PDV ligada à requisição (QR, nº, Separar), processo idempotente, justificar e finalizar
- [x] #9 Etapa 10: entrega pela requisição com os códigos de retirada (ou justificativa)
- [x] #10 Etapa 11: indicadores de qualidade do estoque no detalhe do Local de Estoque
- [ ] #11 Requisição: depois de acrescentar produto pela pesquisa (F1/lupa) ou pelo botão +, o foco volta para o campo de bipar
- [ ] #12 Requisição: a lista de produtos mostra o último acrescentado primeiro
- [ ] #13 Requisição: o botão Separar usa o ícone de carrinho
- [ ] #14 Requisição: imprimir em 3 etapas (via, formato, janela do PDF com o botão Imprimir no cabeçalho); via única pula a etapa 1
- [ ] #15 Requisição: o Imprimir da janela do PDF pergunta a impressora, começando na impressora do PDV
- [x] #16 Requisição: nova e editar em etapas (loja que pede, loja que atende, destino, negócio, quem retira, endereço, produtos, conferência); do negócio, os itens vendidos aparecem desmarcados para marcar o que falta
- [ ] #17 Local que não controla estoque (Renascer, MAB, JDI) não aparece na requisição, no painel de requisições nem na entrega
- [ ] #18 Requisição: quem retira ou recebe em linhas de nome + telefone (pelo menos uma, as duas coisas obrigatórias), com o botão + para mais uma linha e o telefone formatado (99) 9-9999-9999 / (99) 9999-9999 no form, no detalhe e na impressão
- [ ] #19 Requisição: o código de retirada inteiro sai só impresso na via do cliente; a via de separação e a tela (das duas lojas) mostram só as pontas (77XX82); a entrega continua pedindo o código inteiro
- [ ] #20 Requisição: as três vias saem com os itens agrupados por marca (dentro da marca, pela descrição); no A5, com a foto do produto
- [x] #21 Painel de requisições: uma aba por loja no lugar do select; na aba, o card "Para Centro atender" e um card "Centro pediu para Deposito" por loja a que ela pediu, cada um com as requisições agrupadas por status
- [x] #22 Requisições: entregues e canceladas saem do painel (que fica só com as abertas e o botão Todas) e vão para a Listagem de Requisições, no estilo da de negócios: filtros no painel lateral, rolagem infinita, da mais nova para a mais antiga, sem filtro padrão; filtra também por telefone (pedaço acha) e pelo código de retirada inteiro
- [x] #23 PDV: com loja destino (transferência ou venda entre empresas do grupo) a pessoa é a filial da loja destino, preenchida ao escolher a loja destino; a pessoa não abre para troca (clique nem F10) e o fechar no servidor recusa pessoa diferente da filial
- [x] #24 Painel de requisições: a loja da aba fica no endereço (/requisicao/painel/101001) e o F5 continua nela; sem loja no endereço, abre na do PDV; /requisicao passa a ser a listagem
- [x] #25 PDV: o Ligar a uma requisição fica abaixo da natureza e só aparece com a Loja destino preenchida
- [x] #26 PDV: requisição ligada é um item que abre a requisição, e tirar do negócio pede confirmação
- [x] #27 PDV: ligar a uma requisição que não existe é recusado na hora, sem deixar para a sincronização
- [x] #28 Requisição: na impressão, cada item em duas linhas — descrição e quantidade em cima; barras (e localização) e a unidade com a embalagem (CX C/12) embaixo da quantidade
- [x] #29 Requisição: o lado do dispositivo (quem pede ou quem atende) é decidido pelo local de estoque do PDV, não pela filial
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Etapa 6: o model Negocio do MGLara tinha EstoqueLocalDestino apontando para codestoquelocal (devolvia a origem); corrigido junto. PDV com o Dexie antigo de naturezas mostra a Loja destino pela flag transferencia até sincronizar a natureza com movimentadestino.

Etapa 9: na distribuição, a linha que cobre o que falta num item segue com o resto para o próximo item da mesma variação (não joga tudo num item só); fora do pedido fica na requisição mais antiga do negócio. Cancelar ou reabrir um negócio ligado derruba a finalização manual (as justificativas ficam).

Teste de tela no dev (21/10/2026, Chrome headless, PDVs 117 e 511, impressoras fictícias 35/36): roteiro 1–10 percorrido. Corrigido na árvore, aguardando validação: (1) '##' duplicado antes do número (formataCodigo já põe o #) no painel, detalhe, entregas, card do negócio e indicadores; (2) cliente cadastrado não preenchia nome/endereço na requisição (MgSelectPessoa passa a repassar endereço, número, complemento, bairro); (3) telefone do cadastro '(066) 9 ...' saía truncado no detalhe (mostra como veio); (4) reposição mostrava o código de retirada e pedia 'via do cliente'; (5) detalhe no celular espremia o título com os 6 botões; (6) diálogo Imprimir listava impressoras de etiqueta; (7) quem registrou a entrega/criou a requisição vinha vazio (acessor usuariocriacao do MgModel engole a relação UsuarioCriacao); (8) formulário de entrega abria acusando 'Quem recebeu' depois de registrar/estornar; (9) Separar perdia a ligação: dois envios do negócio novo colidiam no uuid (unq_tblnegocio) e a tela recarregava o estado sem a ligação — agora um envio só; (10) cancelar/reabrir negócio de ligação já desfeita derrubava a finalização (requisição entregue voltava a pendente); (11) Locais de Estoque: sigla estourando o avatar, detalhe sem voltar, select de impressora com MgSelectImpressora. Prints: /tmp/claude-t55/prints/index.html.

Painel e listagem (#21/#22, 24/10/2026): o painel pede as abertas dos dois lados da loja (GET v1/requisicao/painel, sem lado/finalizadas) e a tela separa em cards; a listagem é GET v1/requisicao (50 por página, mesma linha do painel via RequisicaoService::SELECT_LISTA). O filtro pelo código de retirada só acha o código inteiro e tem o mesmo limite da entrega (10 por minuto por usuário), senão a listagem viraria o jeito de descobrir o código.

Botões do detalhe (24/10/2026): a requisição 23 (Centro pede para Depósito), aberta no PDV do Centro, não mostrava o Separar. Não é instabilidade: os botões são os pode* do RequisicaoResource, por status + lado do dispositivo + Gerente, e o Separar só aparece no PDV da loja que atende. Regra escrita no doc-6 (decisão 24). O lado do dispositivo é o local de estoque do PDV comparado com o local da requisição (RequisicaoService::ehRequisitante/ehRequisitada), o mesmo que o Fechar confere (RequisicaoNegocioService.php:83); antes passava pela filial do local, sobra do tblpdv.codfilial (TASK-257).

Painel no endereço (#24, 24/10/2026): a aba é /requisicao/painel/{codestoquelocal} e /requisicao virou a listagem (era /requisicao/listagem). Não dá /requisicao/{loja}: /requisicao/{n} é o detalhe, e o QR impresso aponta para ele. Sem loja no endereço, ou com loja fora das abas, o painel redireciona para a do PDV. O voltar do detalhe, da listagem e da nova requisição usa requisicaoStore().enderecoPainel, a loja que estava na tela; a aba não fica mais gravada no navegador.
<!-- SECTION:NOTES:END -->

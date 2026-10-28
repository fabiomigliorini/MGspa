---
id: TASK-55
title: >-
  Loja vende sem estoque e o cliente retira em outra loja com vale de papel;
  entregas e transferências sem controle
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-12 15:53'
updated_date: '2026-10-28 21:17'
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
- [x] #11 Requisição: depois de acrescentar produto pela pesquisa (F1/lupa) ou pelo botão +, o foco volta para o campo de bipar
- [x] #12 Requisição: a lista de produtos mostra o último acrescentado primeiro
- [x] #13 Requisição: o botão Separar usa o ícone de carrinho
- [x] #14 Requisição: imprimir em 3 etapas (via, formato, janela do PDF com o botão Imprimir no cabeçalho); via única pula a etapa 1
- [x] #15 Requisição: o Imprimir da janela do PDF pergunta a impressora, começando na impressora do PDV
- [x] #16 Requisição: nova e editar em etapas (loja que pede, loja que atende, destino, negócio, quem retira, endereço, produtos, conferência); do negócio, os itens vendidos aparecem desmarcados para marcar o que falta
- [x] #17 Local que não controla estoque (Renascer, MAB, JDI) não aparece na requisição, no painel de requisições nem na entrega
- [x] #18 Requisição: quem retira ou recebe em linhas de nome + telefone (pelo menos uma, as duas coisas obrigatórias), com o botão + para mais uma linha e o telefone formatado (99) 9-9999-9999 / (99) 9999-9999 no form, no detalhe e na impressão
- [x] #19 Requisição: o código de retirada inteiro sai só impresso na via do cliente; a via de separação e a tela (das duas lojas) mostram só as pontas (77XX82); a entrega continua pedindo o código inteiro
- [x] #20 Requisição: as três vias saem com os itens agrupados por marca (dentro da marca, pela descrição); no A5, com a foto do produto
- [x] #21 Painel de requisições: uma aba por loja no lugar do select; na aba, o card "Para Centro atender" e um card "Centro pediu para Deposito" por loja a que ela pediu, cada um com as requisições agrupadas por status
- [x] #22 Requisições: entregues e canceladas saem do painel (que fica só com as abertas e o botão Todas) e vão para a Listagem de Requisições, no estilo da de negócios: filtros no painel lateral, rolagem infinita, da mais nova para a mais antiga, sem filtro padrão; filtra também por telefone (pedaço acha) e pelo código de retirada inteiro
- [x] #23 PDV: com loja destino (transferência ou venda entre empresas do grupo) a pessoa é a filial da loja destino, preenchida ao escolher a loja destino; a pessoa não abre para troca (clique nem F10) e o fechar no servidor recusa pessoa diferente da filial
- [x] #24 Painel de requisições: a loja da aba fica no endereço (/requisicao/painel/101001) e o F5 continua nela; sem loja no endereço, abre na do PDV; /requisicao passa a ser a listagem
- [x] #25 PDV: o Ligar a uma requisição fica abaixo da natureza e só aparece com a Loja destino preenchida
- [x] #26 PDV: requisição ligada é um item que abre a requisição, e tirar do negócio pede confirmação
- [x] #27 PDV: ligar a uma requisição que não existe é recusado na hora, sem deixar para a sincronização
- [x] #28 Requisição: na impressão, cada item em duas linhas — descrição e quantidade em cima; barras (e localização) e a unidade com a embalagem (CX C/12) embaixo da quantidade
- [x] #29 Requisição: o lado do dispositivo (quem pede ou quem atende) é decidido pelo local de estoque do PDV, não pela filial
- [x] #30 A loja que pede é a do PDV; só Gerente/Admin escolhe outra (formulário e Gerador; o servidor recusa)
- [x] #31 Ligar recusa na hora a requisição de outra loja atender ou já finalizada/cancelada (Separar, QR e número)
- [x] #32 Detalhe mostra 'Em separação no negócio #X' enquanto há negócio de separação aberto
- [x] #33 ESC volta uma etapa no formulário da requisição (com o select aberto, só fecha o menu)
- [x] #34 Requisição a partir do negócio não pergunta o número do negócio nem quem pede (é a loja da venda)
- [x] #35 Separar com negócio de separação em andamento neste PDV abre esse negócio em vez de criar outro
- [x] #36 Requisição a partir do negócio: 'Marcar todos' na lista de produtos (parcial quando só alguns estão marcados)
- [x] #37 Reabrir a requisição com divergências: Gerente só no PDV da loja que atende, Administrador em qualquer PDV; motivo no Histórico; volta a pendente
- [x] #38 Finalizar sem nada separado → 'Não atendida' (D sem parte, sem mudar o banco): sai dos painéis e do 'já pedido' do Gerador, não liga negócio; Reabrir vale para ela
- [x] #39 Reaberta (ou antes de finalizar), quem atende ou Gerente tira a justificativa de um item (achou o produto); o enviado a mais sai e o produto volta ao fora do pedido
- [x] #40 Finalizar separação só confirma; item que não bateu sem motivo ou fora do pedido sem destino → recusa 'Justifique antes de finalizar' com cada produto; finalizada, o botão some
- [x] #41 Separar só aparece no PDV da loja que atende, com a requisição aberta (A/S/P); no de quem pede, não aparece
- [x] #42 Editar (aguardando), Cancelar e Prioridade só no PDV da loja que pediu (ou Administrador)
- [x] #43 Reposição: o Recebido é só do PDV da loja que pediu (ou Administrador), nunca de quem enviou; a entrega direta pelo negócio (lista de Entregas) recusa negócio ligado a requisição
- [x] #44 Os botões da requisição são do lado do PDV: Gerente não faz o serviço do outro lado (Separar, Justificar, Finalizar, Entregar ao cliente, Editar, Cancelar, Recebido, via do cliente com o código); só o Administrador age em qualquer PDV
- [x] #45 A linha da requisição é só 'quem pediu → quem atende' (sem o nome do cliente), com o destino embaixo, no painel e na listagem (os grupos do painel estão no #52)
- [x] #46 Painel com três cards: Atender (o que a loja tem para separar), Separados (o que ela separou e ainda não saiu) e '{Loja} pediu' (tudo o que ela pediu, de qualquer loja ou Compras, num card só)
- [x] #47 Entregar no endereço: a entrega ao cliente é da loja que pediu (o entregador dela busca na loja que atende); a loja que atende só tem o Entregar ao cliente na retirada (R)
- [x] #48 Entregar no endereço: a loja que atende registra 'Retirado pelo entregador' (quem buscou, sem código) em cada parte separada; a transferência sai do 'A receber' e a requisição fica 'Saiu para entrega'
- [x] #49 Entregar no endereço: a loja que pediu registra 'Entregar ao cliente' com o código de retirada (ou justificativa), só nas partes já retiradas; tentativa não entregue fica registrada; todas entregues → Entregue
- [x] #50 Entregar no endereço: a via de separação e o detalhe dizem que o entregador da loja que pediu vem buscar (os grupos do painel estão no #52)
- [x] #51 Negócio de separação que já saiu da loja (retirado pelo entregador, entregue ao cliente ou recebido) não reabre: recusa pedindo para estornar a retirada/entrega antes
- [x] #52 Um nome por situação em todo lugar (A separar · Em separação · Separado · Saiu para entrega · Entregue · Cancelada · Não atendida; tabela na decisão 14 do doc-6); no painel, os grupos são esses nomes (no card Separados, sem subtítulo), com o ícone do destino na linha; destino com um nome só (Cliente retira · Entrega no endereço · Reposição)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Etapa 6: o model Negocio do MGLara tinha EstoqueLocalDestino apontando para codestoquelocal (devolvia a origem); corrigido junto. PDV com o Dexie antigo de naturezas mostra a Loja destino pela flag transferencia até sincronizar a natureza com movimentadestino.

Etapa 9: na distribuição, a linha que cobre o que falta num item segue com o resto para o próximo item da mesma variação (não joga tudo num item só); fora do pedido fica na requisição mais antiga do negócio. Cancelar ou reabrir um negócio ligado derruba a finalização manual (as justificativas ficam).

Teste de tela no dev (21/10/2026, Chrome headless, PDVs 117 e 511, impressoras fictícias 35/36): roteiro 1–10 percorrido. Corrigido na árvore, aguardando validação: (1) '##' duplicado antes do número (formataCodigo já põe o #) no painel, detalhe, entregas, card do negócio e indicadores; (2) cliente cadastrado não preenchia nome/endereço na requisição (MgSelectPessoa passa a repassar endereço, número, complemento, bairro); (3) telefone do cadastro '(066) 9 ...' saía truncado no detalhe (mostra como veio); (4) reposição mostrava o código de retirada e pedia 'via do cliente'; (5) detalhe no celular espremia o título com os 6 botões; (6) diálogo Imprimir listava impressoras de etiqueta; (7) quem registrou a entrega/criou a requisição vinha vazio (acessor usuariocriacao do MgModel engole a relação UsuarioCriacao); (8) formulário de entrega abria acusando 'Quem recebeu' depois de registrar/estornar; (9) Separar perdia a ligação: dois envios do negócio novo colidiam no uuid (unq_tblnegocio) e a tela recarregava o estado sem a ligação — agora um envio só; (10) cancelar/reabrir negócio de ligação já desfeita derrubava a finalização (requisição entregue voltava a pendente); (11) Locais de Estoque: sigla estourando o avatar, detalhe sem voltar, select de impressora com MgSelectImpressora. Prints: /tmp/claude-t55/prints/index.html.

Painel e listagem (#21/#22, 24/10/2026): o painel pede as abertas dos dois lados da loja (GET v1/requisicao/painel, sem lado/finalizadas) e a tela separa em cards; a listagem é GET v1/requisicao (50 por página, mesma linha do painel via RequisicaoService::SELECT_LISTA). O filtro pelo código de retirada só acha o código inteiro e tem o mesmo limite da entrega (10 por minuto por usuário), senão a listagem viraria o jeito de descobrir o código.

Botões do detalhe (24/10/2026): a requisição 23 (Centro pede para Depósito), aberta no PDV do Centro, não mostrava o Separar. Não é instabilidade: os botões são os pode* do RequisicaoResource, por status + lado do dispositivo + Gerente, e o Separar só aparece no PDV da loja que atende. Regra escrita no doc-6 (decisão 24). O lado do dispositivo é o local de estoque do PDV comparado com o local da requisição (RequisicaoService::ehRequisitante/ehRequisitada), o mesmo que o Fechar confere (RequisicaoNegocioService.php:83); antes passava pela filial do local, sobra do tblpdv.codfilial (TASK-257).

Painel no endereço (#24, 24/10/2026): a aba é /requisicao/painel/{codestoquelocal} e /requisicao virou a listagem (era /requisicao/listagem). Não dá /requisicao/{loja}: /requisicao/{n} é o detalhe, e o QR impresso aponta para ele. Sem loja no endereço, ou com loja fora das abas, o painel redireciona para a do PDV. O voltar do detalhe, da listagem e da nova requisição usa requisicaoStore().enderecoPainel, a loja que estava na tela; a aba não fica mais gravada no navegador.

24/10/2026: o "Sugerir abaixo do mínimo" do AC #5 (botão no formulário + GET v1/requisicao/sugestao, commit 86f4ac958) saiu: não funcionava e não foi pedido. A reposição pelo mínimo foi para a TASK-258 (gerador de requisição). O PDF Sugestão de Transferências do estoque continua.

Go-live 26/10: produção quebrou no painel (column r.nomes does not exist). A tblrequisicao de prod foi criada pela versão de 21/10 do requisicao.sql (nome/telefone); a de 24/10 trocou para nomes/telefones dentro do CREATE TABLE IF NOT EXISTS, que não mexe na tabela existente. O requisicao.sql agora converte nome/telefone em nomes/telefones (telefone só dígitos, até 11) e cria os checks que faltarem; reaplicável. Já aplicado em prod em 26/10 (4 requisições convertidas; a nº 3 ficou sem telefone, o antigo estava vazio).

Painel 28/10/2026: a regra da antiga AC #40 (separada sai de quem separou) mudou a pedido do usuário — quem separou quer acompanhar a transferência até ser entregue, num card separado. GET v1/requisicao/painel traz A/S/P/T/D dos dois lados (RequisicaoService::painel); a tela separa em 'Para X atender' (A/S/P), 'X separou, falta entregar' (T/D por destino) e 'X pediu para Y'. Status gravados não mudam: só os nomes dos grupos do painel. Seta pelo helper trajeto() de stores/requisicao.js (painel e listagem); na listagem a legenda ganhou 'pedida por'.

Recebido da reposição (28/10/2026): Gerente no PDV do Depósito dava o Recebido da reposição que o próprio Depósito separou (#73/#74 da massa de teste). RequisicaoEntregaService::podeEntregar: na L, requisitante ou Gerente que não esteja na requisitada. NegocioEntregaService::registrar recusava a entrega direta só de negócio de requisição R/E; agora de qualquer destino (a lista de Entregas era o outro caminho para quem enviou marcar o recebido).

Botões pelo lado do PDV (28/10/2026): no PDV do Botânico (quem pede) o Gustavo, Gerente, via Separar e Finalizar da #69. Separar aparecia em qualquer PDV por decisão (antiga AC #32, doc-6 decisão 24) e os pode* aceitavam 'ou Gerente' em qualquer PDV, então o Gerente fazia o serviço do outro lado (Finalizar/Justificar, Editar, Cancelar, Entregar, via do cliente com o código inteiro). Regra nova: o lado do PDV decide; Gerente só onde precisa de autoridade dentro do lado (Reabrir, na loja que atende) e para criar em nome de outra loja (AC #30, Gerador); Administrador (RequisicaoService::ehAdministrador = Autorizador::pode([])) em qualquer PDV. Detalhe: o Separar usa r.podeseparar.

Linha da requisição (28/10/2026): a seta no sentido da mercadoria ('BOT → Luiz') confundiu; volta a 'quem pediu → quem atende' (CEN → BOT), sem o nome do cliente, e embaixo o destino (cliente retira · 1 item), sem o 'pedida por'.

Cards do painel (28/10/2026): um card por loja a que se pediu ficava solto; agora três cards fixos, Atender / Separados / Pedidos. Os grupos de dentro ficam como estão por enquanto: o usuário vai decidir a unificação dos substatus depois.

Entrega no endereço pela loja que pediu (28/10/2026): na empresa, a loja que pede já tem a venda; o entregador dela passa na loja que atende, busca a mercadoria e entrega ao cliente, e a entrega é responsabilidade de quem pediu. Hoje o sistema põe a entrega ao cliente do E na loja que atende (RequisicaoEntregaService::podeEntregar com ehRequisitada para R e E). Decidido: R (cliente retira) continua com quem atende; o E tem dois passos, cada um um botão: 'Retirado pelo entregador' (quem atende, nome de quem buscou, sem código; é passagem de mão, não quem enviou confirmando o próprio recebido da decisão 19) e 'Entregar ao cliente' (quem pediu, com o código de retirada ou justificativa); o código continua no E (o cliente fica com a via e informa ao entregador). Fluxo: separa (T/D) → retirado pelo entregador → 'Com o entregador' → entregue ao cliente → E. Proposta técnica, a conferir na implementação: tblnegocioentrega.etapa (C retirada / E cliente; os registros existentes ficam E), a retirada preenche tblnegocio.entregue pelo atualizarEntregue de hoje (primeiro E/P); status novo 'Com o entregador' no CHECK de tblrequisicao.status e no RequisicaoProcessamentoService; podeEntregar com E = ehRequisitante e podeRetirar com E = ehRequisitada no RequisicaoResource; partesPendentes separando falta retirar de falta entregar ao cliente.

Implementação #47–#50 (28/10/2026): tblnegocioentrega.etapa (C retirada pelo entregador / E cliente) e status R 'Com o entregador' em api/database/requisicao_entregador.sql — go-live: rodar ANTES do deploy (o calcular da requisição lê a etapa). RequisicaoEntregaService: podeRetirar/retirar (POST v1/requisicao/{id}/retirar, loja que atende), partesARetirar; podeEntregar só é da loja que atende no R (E e L, de quem pediu); no E, partesPendentes = retiradas e ainda não entregues ao cliente. RequisicaoProcessamentoService: entreguecliente por negócio; no E, Entregue quando todas as partes chegaram ao cliente e R quando todas foram retiradas. Painel: R só no lado de quem pediu. NegocioEntregaService::inativar reprocessa sempre (estornar a entrega ao cliente não muda o tblnegocio.entregue).

Nomes unificados (28/10/2026): os grupos com o nome da ação de cada lado (antigo #45) e os da entrega no endereço (#50: Entregador vem buscar, Buscar em XXX, Com o entregador) saíram a pedido do usuário — 'salada de fruta'. Decidido caso a caso: A 'A separar'; S e P 'Em separação'; T e D 'Separado' (D mostra 'com divergências' na legenda, agora também na listagem — legenda() em stores/requisicao.js); R 'Saiu para entrega'; E, C e Não atendida sem mudança. Painel: GRUPOS = A, S, T, R com grupoDe (P→S, D→T) nos três cards. Filtro da listagem: FILTRO_STATUS manda 'S,P' / 'T,D' e o backend aceita a lista (RequisicaoService::pesquisar). Destino: Cliente retira / Entrega no endereço / Reposição. Partes do detalhe: 'retirado pelo entregador' e 'entregue'. Recusa de ligar: 'não aceita mais separação (situação: X)'. Os critérios #45 e #50 ficam superados pela regra nova (o #52 descreve).
<!-- SECTION:NOTES:END -->

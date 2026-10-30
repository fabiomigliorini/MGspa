---
id: TASK-261
title: Requisição não mostra quem pediu nem quando a reposição saiu da loja
status: In Progress
assignee:
  - '@eduardo'
created_date: '2026-10-30 15:32'
updated_date: '2026-10-30 19:25'
labels:
  - negocios
dependencies: []
priority: medium
type: feature
ordinal: 269000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido de 29/10/2026, depois do go-live da requisição (TASK-55): o painel e a listagem não dizem quem pediu; o painel tem recuos e colunas desalinhados (bolinha de 40px na linha, grupo com outro recuo, prioridade e tempo mudando de lugar) e deve ficar como uma listagem, no espaçamento da /vale-modelo; no detalhe, o nome do produto não leva ao cadastro no MGLara; e na reposição não há confirmação de que a mercadoria saiu da loja que atende.

Decidido em 30/10/2026: linha em colunas alinhadas (usuário em coluna própria, sem cabeçalho de colunas); na reposição o Recebido só depois do 'Saiu para entrega', com o mesmo código da 'Retirado pelo entregador' da entrega no endereço (o nome é o do funcionário da loja que atende). Na reposição a saída não dá a transferência por entregue: ela continua no A receber até o Recebido (decisão 19 do doc-6). Plano: /home/usuario/.claude/plans/preciso-de-duas-melhorias-soft-pinwheel.md
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Painel e listagem de requisições mostram o usuário que pediu na primeira linha
- [ ] #2 Painel em formato de listagem: card, grupo e linha no mesmo recuo; usuário, prioridade e tempo em colunas alinhadas; botão + não cobre a última linha
- [ ] #3 No detalhe da requisição, o nome do produto (pedido, substituto e fora do pedido) abre o cadastro no MGLara em nova aba, sem abrir o Justificar
- [ ] #4 Reposição: a loja que atende registra 'Saiu para entrega' com o nome de quem leva; a requisição fica 'Saiu para entrega' e o Recebido só aparece depois
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementado em 30/10/2026, aguardando validação (sem commit).

Quem pediu: RequisicaoService::SELECT_LISTA traz uc.usuario as usuariocriacao (left join tblusuario), para o painel e a listagem. Requisições de teste criadas por script não têm usuário: a coluna fica vazia.

Linha: negocios/src/components/requisicao/RequisicaoLinha.vue, usada pelo painel e pela listagem. Ícone do destino (q-icon, sem q-avatar) na seção avatar e grid col-8/col-sm-5 (código · trajeto + legenda), col-4/col-sm-3 (usuário), col-6/col-sm-2 (q-badge: prioridade no painel, situação na listagem), col-6/col-sm-2 (quando). Sem seta. Painel: um q-list separator por card; título do card e do grupo viram q-item com a mesma seção avatar, então os três textos começam em 72px; div q-py-xl no fim por causa do botão +. Conferido em réplica headless (Vue + Quasar UMD do node_modules, dados reais do painel do Depósito) em 1600, 800 e 430px.

Link: components/MgLinkProduto.vue (shared), classe q-link do Quasar (sem sublinhado, sem style inline), @click.stop e @keyup.stop para não abrir o Justificar da linha. No detalhe: item, substituto e fora do pedido.

Reposição: Requisicao::DESTINOS_COM_SAIDA = [E, L]. O processo lê os dois passos das etapas (retirado = etapa C, entreguecliente = etapa E). R quando toda parte tem retirado ou entreguecliente (parte recebida antes de existir a saída conta como saída). A saída da L não preenche tblnegocio.entregue (atualizarEntregue ignora etapa C de requisição L): fica no A receber até o Recebido. Reabrir negócio recusa com etapa C ativa. Gerador conta R no já pedido. Textos por destino no SAIDA de stores/requisicao.js; CardEntrega e EntregaPage mostram 'Saiu para entrega · levou X' (entregas() traz destinorequisicao por subquery). Sem DDL. Recalculado o status das 31 requisições do dev sem gravar: nenhuma muda pela regra nova (as 70 e 77 estão S sem negócio ligado, gravado velho, anterior a esta task).

Roteiro de teste: /requisicao/painel/<loja> e /requisicao (alinhamento, usuário); /requisicao/<id> com o PDV de quem atende e Finalizar disponível (clicar no produto abre o MGLara e não o Justificar; Ctrl+Shift+R por causa do @components); reposição Separada: no PDV de quem atende, botão Saiu para entrega (ícone de aperto de mão), nome de quem levou → status Saiu para entrega e sai do card Separados; no PDV de quem pediu, aparece em '{Loja} pediu → Saiu para entrega' e o Recebido só agora; /negocio/<separação> mostra 'Saiu para entrega · levou X' e reabrir é recusado; estornar a saída antes do Recebido volta para Separado. Entrega no endereço continua igual.

Testes de 30/10/2026, no banco de dev dentro de transação desfeita (rollback; nada gravado), como usuário sem Administrador (o luiz é Administrador no dev e age nos dois lados, o que falseia os testes de lado): 43 verificações OK. Reposição #73 (CEN pede, DEP atende): sem saída não há Recebido (403); saída só no PDV de quem atende; saída sem nome recusada; saída → R sem preencher tblnegocio.entregue; continua no A receber da CEN e nas Entregas a fazer do DEP; sai do painel do DEP e fica no da CEN; entregas do negócio trazem destino L e quem levou; reabrir recusado ('já saiu para entrega'); estornar a saída volta a T; Recebido → E e preenche o entregue; estornar a saída com Recebido ativo recusado; estornar o Recebido volta a R; entrega direta pelo negócio recusada com o texto da reposição. Regressão: entrega no endereço #88 (retirada continua marcando o entregue, entrega ao cliente com código) e cliente retira #80 iguais. Recalculado o status de todas as outras: só 70 e 77 divergem (S gravado sem negócio ligado, anterior).

Achado na revisão e corrigido: quem pediu não via no detalhe quem está levando (o nome só aparecia no card de entrega do negócio). O processo traz retiradopor (o recebedor da etapa C) e as partes mostram 'saiu para entrega 30/10 14:20 (levou João)'.

Link do produto testado no Chrome headless: clique e Enter no link não disparam a linha; clique no resto da linha e Enter na linha disparam; href MGLARA_URL/produto/{cod}, target _blank, sem sublinhado. SFCs compilados conferindo identificador do template fora do setup: nenhum.

30/10 (validação): código e lojas em colunas separadas na RequisicaoLinha. Uma linha com o título das colunas (como o cabeçalho da /vale-modelo) chegou a ser feita e foi desfeita a pedido. Conferido em réplica headless a 1600, 800 e 430px.

30/10 (validação, 2ª rodada): o painel não tem mais a linha de grupo; a situação virou coluna da linha (ícone e texto na cor dela), à esquerda da prioridade (etiqueta), nas duas telas: a listagem passa a mostrar a prioridade também. A ordem do painel continua por situação (A separar, Em separação, Separado, Saiu para entrega) e, nela, a do backend. Quem pediu foi para baixo do código; a Criada usa a fonte do trajeto (body2). Réplica headless a 1600, 800, 724 (listagem com filtro numa tela de 1024), 430 e 390px: tudo cabe; só a 390px o 'Saiu para entrega' corta em 'Saiu para entr…' (no celular a situação já perde o ícone).
<!-- SECTION:NOTES:END -->

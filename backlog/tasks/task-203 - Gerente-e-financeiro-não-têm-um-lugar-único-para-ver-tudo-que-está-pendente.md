---
id: TASK-203
title: Gerente e financeiro não têm um lugar único para ver tudo que está pendente
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-10-07 19:27'
updated_date: '2026-10-13 16:29'
labels:
  - contas
dependencies: []
priority: medium
type: feature
ordinal: 214000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido do Fábio (07/10/2026): uma dashboard que resume todas as pendências (períodos de maquineta abertos/pendentes/sem borderô, caixas pendentes, transferências a confirmar, cheques, vales, duplicatas, vendas com diferença, PIX a confirmar...). Cada item leva à tela onde está a pendência. A mesma dashboard tem a visão do gerente (só a unidade dele) e a do financeiro (geral). Substitui a lista da tela Fechamentos.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Painel da filial: caixas, maquinetas, ocorrências, negócios abertos em PDV monitorado, confissão faltando, vendas com diferença e PIX/depósito a confirmar, cada item levando à tela que resolve
- [ ] #2 Cheques e vales a conferir no painel (precisa de tela própria antes)
- [x] #3 Lista de filiais como entrada do painel, com o total de cada uma
- [x] #4 Definir quem pode ver o painel de qual filial
- [x] #5 Eliminar a tela Fechamentos depois do painel validado
- [x] #6 Período aberto com movimento é pendência, no caixa e na maquineta; aberto sem movimento não é
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Período aberto no painel (13/10/2026, AC #6, corrige a regra de 10/10): período aberto com movimento é pendência; aberto sem movimento, não. Caixa: entra o período aberto com algum tblportadormovimento ativo, além do período com diferença a resolver e da transferência a confirmar (PainelService::caixas). Maquineta: entra o período aberto com cartão (pagamento com codmaquinetalote ou codmaquinetalotecancelamento no período), além do período encerrado pendente e do sem borderô; a linha mostra 'Aberto desde' (MaquinetaLoteService::resumo devolve abertoComMovimento). A regra de 10/10 tirava todo período aberto e em produção, no primeiro dia depois do go-live, com tudo aberto, o painel ficou sem caixa e sem maquineta. A maquineta nunca tinha contado o período aberto (desde o c2d163a21): no dev ela só aparecia com período já encerrado, pendente ou sem borderô.

Layout (10/10/2026): filial em abas (uma por filial ativa, link /painel/{codfilial}) no lugar do select; cards em duas colunas a partir de 1024px (col-12 col-md-6); PIX e depósitos antes das confissões. 'Ver todas' das ocorrências abre o livro filtrado pela filial e A conferir (a tela de ocorrências aceita os filtros na URL e parte do padrão).

Lista das filiais e acesso (10/10/2026, AC #3 e #4): /painel sem filial lista as filiais ativas que o usuário vê, um card por empresa (ordem codempresa, codfilial; três por linha a partir de 1024px, dois no tablet, um no celular: col-12 col-sm-6 col-md-4), com o total de pendências de cada uma (soma dos cards: caixas + maquinetas + ocorrências + negócios esquecidos + conferência), e cada linha abre /painel/{codfilial}; no painel da filial, voltar leva à lista (as abas por filial saíram). Acesso pela regra da conferência (ConferenciaAutorizador): Financeiro e Administrador veem todas; Gerente só as filiais em que tem o grupo Gerente (tblgrupousuariousuario.codfilial). GET v1/painel -> PainelController@index -> PainelService::filiais(ConferenciaAutorizador::filiais()); GET v1/filial/{codfilial}/painel agora recusa com 403 a filial de outro gerente. O total chama PainelService::filial por filial (~1,3 s para as 20 filiais no dev). A filial não fica mais guardada no navegador: a entrada é sempre a lista.

Tela Fechamentos eliminada (10/10/2026): saem a página contas/pages/fechamento/Index.vue, a rota e o item do menu, o GET v1/conferencia (ConferenciaController::index, ConferenciaService::pendencias/filiais) e a lista do conferenciaStore. A Venda com diferença (fechamento/venda/:id) fica, porque é para ela que o painel leva; o voltar dela vai para o painel.

O que a Fechamentos fazia e o painel ainda não faz:
- Cheques e vales recebidos no PDV, item a item, com o botão 'Está aqui, conferido' (POST v1/conferencia/pagamento/{id}/conferir, que continua na API, sem tela). Até o #2 sair, não há onde conferir cheque e vale. O PainelService::filial tira os dois de propósito.
- PIX e depósito a confirmar: a Fechamentos só mostrava para Financeiro e Administrador; o painel mostra também ao Gerente (PainelService::filial chama ConferenciaService::consultar com $comPix = true).
- Já coberto: todas as filiais juntas, que a Fechamentos mostrava quando a filial ficava em branco, agora é a lista de filiais do #3. A duplicata anexava a confissão ali mesmo (scanner); no painel ela leva ao negócio, que anexa.

Ocorrências no painel da filial (10/10/2026): o card usa a mesma linha do livro (@components/MgOcorrenciaItem.vue) e cada ocorrência abre a tela dela (ocorrencia-detalhe); 'Ver todas' continua abrindo o livro filtrado. Backend: OcorrenciaService::listar(where, params, ordem, limite) devolve as ocorrências no formato da linha do livro (com as auditorias), usado por PainelService::ocorrencias. Cards das empresas na lista com a mesma altura na fileira (full-height).
<!-- SECTION:NOTES:END -->

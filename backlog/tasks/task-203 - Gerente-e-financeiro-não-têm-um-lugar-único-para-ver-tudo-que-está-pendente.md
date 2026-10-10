---
id: TASK-203
title: Gerente e financeiro não têm um lugar único para ver tudo que está pendente
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-10-07 19:27'
updated_date: '2026-10-10 21:40'
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
- [ ] #3 Lista de filiais como entrada do painel, com o total de cada uma
- [ ] #4 Definir quem pode ver o painel de qual filial
- [x] #5 Eliminar a tela Fechamentos depois do painel validado
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Período aberto não é pendência no painel (10/10/2026): a maquineta só conta período encerrado pendente ou sem borderô; o caixa só conta período com diferença a resolver ou transferência a confirmar. Caixa aberto, com ou sem lançamento, não aparece (PainelService::caixas).

Layout (10/10/2026): filial em abas (uma por filial ativa, link /painel/{codfilial}) no lugar do select; cards em duas colunas a partir de 1024px (col-12 col-md-6); PIX e depósitos antes das confissões. 'Ver todas' das ocorrências abre o livro filtrado pela filial e A conferir (a tela de ocorrências aceita os filtros na URL e parte do padrão).

Tela Fechamentos eliminada (10/10/2026): saem a página contas/pages/fechamento/Index.vue, a rota e o item do menu, o GET v1/conferencia (ConferenciaController::index, ConferenciaService::pendencias/filiais) e a lista do conferenciaStore. A Venda com diferença (fechamento/venda/:id) fica, porque é para ela que o painel leva; o voltar dela vai para o painel.

O que a Fechamentos fazia e o painel ainda não faz:
- Cheques e vales recebidos no PDV, item a item, com o botão 'Está aqui, conferido' (POST v1/conferencia/pagamento/{id}/conferir, que continua na API, sem tela). Até o #2 sair, não há onde conferir cheque e vale. O PainelService::filial tira os dois de propósito.
- PIX e depósito a confirmar: a Fechamentos só mostrava para Financeiro e Administrador; o painel mostra também ao Gerente (PainelService::filial chama ConferenciaService::consultar com $comPix = true).
- Já coberto: todas as filiais juntas, que a Fechamentos mostrava quando a filial ficava em branco, agora é a lista de filiais do #3. A duplicata anexava a confissão ali mesmo (scanner); no painel ela leva ao negócio, que anexa.
<!-- SECTION:NOTES:END -->

---
id: TASK-207
title: Menu do negócios esconde PagarMe e Prancheta dentro de Configuração
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-10-10 18:31'
updated_date: '2026-10-10 18:40'
labels:
  - negocios
dependencies: []
priority: low
type: enhancement
ordinal: 219000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
O item Configuração do menu de apps do negócios só abre uma tela com drawer para duas coisas: PagarMe (pedidos pendentes das maquininhas POS) e Prancheta. Viram dois itens do menu e o /config acaba.

Junto: o negócios tinha 19 layouts que eram cópia do MainLayout mudando só título, voltar e drawer. Os outros apps (contas, estoque, agro, notas) já usam um MainLayout só, guiado por route.meta (title, leftDrawer, rightDrawer); o negócios passa a seguir o mesmo padrão, com meta.backTo (string ou função da rota) para o botão voltar do cabeçalho.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 PagarMe e Prancheta como itens do menu (Administração), /config removido
- [x] #2 Layouts do negócios unificados no MainLayout por route.meta (sobram MainLayout e QuiosqueLayout)
- [ ] #3 Tela PagarMe não quebra (nem trava a navegação) com pedido pendente sem maquininha vinculada
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementação:
- negocios/src/layouts/MainLayout.vue virou o layout de todas as rotas (exceto quiosque e orçamentos): título, voltar e drawers vêm de route.meta (title, backTo, leftDrawer, rightDrawer), como no estoque/contas/agro/notas. meta.backTo é string ou função da rota (Devolução volta pro negócio do :uuid; Dispositivo volta pro PDV se for o próprio aparelho, senão pra lista). UsuarioConectado direto no toolbar.
- router/routes.js: um pai com o MainLayout e meta em cada filha; /config sai, entram /pagar-me e /prancheta. Sem redirect do /config antigo.
- Apagados 18 layouts-cópia + ConfigLeftDrawer. Sobram MainLayout e QuiosqueLayout.
- Drawer da Confissão (era inline no layout) -> components/drawers/ConfissaoLeftDrawer.vue.
- configurar() da lista de pagamentos (era no PagamentoLayout) -> utils/pagamentoLista.js, chamado na PagamentoPage e no PagamentoLeftDrawer (igual ao contas).
- boot/axios.js pegava o useAuthStore() no topo do módulo: qualquer import dele antes do Pinia (ex.: routes.js -> stores/sincronizacao) derrubava o app inteiro. Passou pra dentro dos interceptors; com isso o import dinâmico de contorno no beforeEach do router/index.js virou import normal.

Teste: smoke em Chrome headless passando por todas as rotas — título, voltar e drawers conferem; /config/pagar-me cai no 404; quiosque sem cabeçalho.
<!-- SECTION:NOTES:END -->

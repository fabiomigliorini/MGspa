import { defineAsyncComponent } from 'vue'
import { PERMISSOES } from 'src/constants/permissoes'

const routes = [
  {
    path: '/',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        name: 'home',
        component: () => import('pages/SaldosPage.vue'),
        meta: {
          auth: true,
          title: 'Saldos',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
        },
      },
      {
        path: 'banco',
        name: 'banco',
        component: () => import('pages/banco/Index.vue'),
        meta: {
          auth: true,
          title: 'Bancos',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
          leftDrawer: defineAsyncComponent(() =>
            import('components/drawers/BancoFiltrosDrawer.vue'),
          ),
        },
      },
      {
        path: 'conta-contabil',
        name: 'conta-contabil',
        component: () => import('pages/contaContabil/Index.vue'),
        meta: {
          auth: true,
          title: 'Contas Contábeis',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
          leftDrawer: defineAsyncComponent(() =>
            import('components/drawers/ContaContabilFiltrosDrawer.vue'),
          ),
        },
      },
      {
        path: 'tipo-movimento-titulo',
        name: 'tipo-movimento-titulo',
        component: () => import('pages/tipoMovimentoTitulo/Index.vue'),
        meta: {
          auth: true,
          title: 'Tipos de Movimentos de Títulos',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
          leftDrawer: defineAsyncComponent(() =>
            import('components/drawers/TipoMovimentoTituloFiltrosDrawer.vue'),
          ),
        },
      },
      {
        path: 'tipo-titulo',
        name: 'tipo-titulo',
        component: () => import('pages/tipoTitulo/Index.vue'),
        meta: {
          auth: true,
          title: 'Tipos de Títulos',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
          leftDrawer: defineAsyncComponent(() =>
            import('components/drawers/TipoTituloFiltrosDrawer.vue'),
          ),
        },
      },
      {
        path: 'portador',
        name: 'portador',
        component: () => import('pages/portador/Index.vue'),
        meta: {
          auth: true,
          title: 'Portadores',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
          leftDrawer: defineAsyncComponent(() =>
            import('components/drawers/PortadorFiltrosDrawer.vue'),
          ),
        },
      },
      {
        path: 'forma-pagamento',
        name: 'forma-pagamento',
        component: () => import('pages/formaPagamento/Index.vue'),
        meta: {
          auth: true,
          title: 'Formas de Pagamento',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
          leftDrawer: defineAsyncComponent(() =>
            import('components/drawers/FormaPagamentoFiltrosDrawer.vue'),
          ),
        },
      },
      {
        path: 'pix',
        name: 'pix',
        component: () => import('pages/pix/Index.vue'),
        meta: {
          auth: true,
          title: 'Pix',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
          leftDrawer: defineAsyncComponent(() =>
            import('components/drawers/PixFiltrosDrawer.vue'),
          ),
        },
      },
      {
        path: 'extrato/:id/:mesAno',
        name: 'extrato',
        component: () => import('pages/MovimentacoesPage.vue'),
        meta: {
          auth: true,
          title: 'Extrato',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
        },
      },
      {
        path: 'sem-permissao',
        name: 'sem-permissao',
        component: () => import('pages/SemPermissaoPage.vue'),
        meta: { auth: false, title: 'Sem permissão' },
      },
    ],
  },

  {
    path: '/login',
    name: 'login',
    component: () => import('pages/Login.vue'),
    meta: { auth: false },
  },

  {
    path: '/:catchAll(.*)*',
    name: 'error404',
    component: () => import('pages/ErrorNotFound.vue'),
  },
]

export default routes

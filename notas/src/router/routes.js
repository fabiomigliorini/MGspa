import NotasFiltrosDrawer from 'src/components/drawers/NotasFiltrosDrawer.vue'
import NotasDetalhesDrawer from 'src/components/drawers/NotasDetalhesDrawer.vue'
import TributacaoFiltrosDrawer from 'src/components/drawers/TributacaoFiltrosDrawer.vue'
import TributacaoSimuladorDrawer from 'src/components/drawers/TributacaoSimuladorDrawer.vue'

const routes = [
  {
    path: '/',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        name: 'home',
        component: () => import('pages/IndexPage.vue'),
        meta: {
          auth: true,
          // SEM drawers = botões desabilitados
        },
      },
      {
        path: 'notas',
        name: 'notas',
        component: () => import('pages/NotasPage.vue'),
        meta: {
          auth: true,
          permissions: ['Administrador', 'Financeiro'],
          leftDrawer: NotasFiltrosDrawer, // <-- Drawer esquerda
          rightDrawer: NotasDetalhesDrawer, // <-- Drawer direita
        },
      },
      {
        path: 'notas/criar',
        name: 'nota-fiscal-create',
        component: () => import('pages/NotaFiscalFormPage.vue'),
        meta: {
          auth: true,
          permissions: ['Administrador', 'Financeiro'],
        },
      },
      {
        path: 'notas/:codnotafiscal',
        name: 'nota-fiscal-view',
        component: () => import('pages/NotaFiscalViewPage.vue'),
        meta: {
          auth: true,
          permissions: ['Administrador', 'Financeiro'],
        },
      },
      {
        path: 'notas/:codnotafiscal/editar',
        name: 'nota-fiscal-edit',
        component: () => import('pages/NotaFiscalFormPage.vue'),
        meta: {
          auth: true,
          permissions: ['Administrador', 'Financeiro'],
        },
      },
      {
        path: 'notas/:codnotafiscal/item/adicionar',
        name: 'nota-fiscal-item-adicionar',
        component: () => import('pages/items/ItemFormAdicionarPage.vue'),
        meta: {
          auth: true,
          permissions: ['Administrador', 'Financeiro'],
        },
      },
      {
        path: 'notas/:codnotafiscal/item/:codnotafiscalprodutobarra/dados',
        name: 'nota-fiscal-item-dados',
        component: () => import('pages/items/ItemFormDadosPage.vue'),
        meta: {
          auth: true,
          permissions: ['Administrador', 'Financeiro'],
        },
      },
      {
        path: 'notas/:codnotafiscal/item/:codnotafiscalprodutobarra/tributos-legado',
        name: 'nota-fiscal-item-tributos-legado',
        component: () => import('pages/items/ItemFormTributosLegadoPage.vue'),
        meta: {
          auth: true,
          permissions: ['Administrador', 'Financeiro'],
        },
      },
      {
        path: 'notas/:codnotafiscal/item/:codnotafiscalprodutobarra/tributos',
        name: 'nota-fiscal-item-tributos',
        component: () => import('pages/items/ItemFormTributosPage.vue'),
        meta: {
          auth: true,
          permissions: ['Administrador', 'Financeiro'],
        },
      },
    ],
  },

  {
    path: '/tributacao',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        name: 'tributacao',
        component: () => import('pages/TributacaoPage.vue'),
        meta: {
          auth: true,
          leftDrawer: TributacaoFiltrosDrawer,
          rightDrawer: TributacaoSimuladorDrawer,
        },
      },
    ],
  },

  {
    path: '/login',
    name: 'login',
    component: () => import('pages/LoginPage.vue'),
    meta: { auth: false },
  },

  {
    path: '/:catchAll(.*)*',
    name: 'error404',
    component: () => import('pages/ErrorNotFound.vue'),
  },
]

export default routes

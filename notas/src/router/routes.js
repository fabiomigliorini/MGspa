import NotasFiltrosDrawer from 'src/components/drawers/NotasFiltrosDrawer.vue'
import NotasDetalhesDrawer from 'src/components/drawers/NotasDetalhesDrawer.vue'
import TributacaoFiltrosDrawer from 'src/components/drawers/TributacaoFiltrosDrawer.vue'
import TributacaoSimuladorDrawer from 'src/components/drawers/TributacaoSimuladorDrawer.vue'
import TributacaoCadastroFiltrosDrawer from 'src/components/drawers/TributacaoCadastroFiltrosDrawer.vue'
import CfopFiltrosDrawer from 'src/components/drawers/CfopFiltrosDrawer.vue'
import CidadeFiltrosDrawer from 'src/components/drawers/CidadeFiltrosDrawer.vue'

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
          title: 'Notas & Documentos Fiscais',
        },
      },
      {
        path: 'nota',
        name: 'notas',
        component: () => import('pages/NotasPage.vue'),
        meta: {
          auth: true,
          title: 'Notas Fiscais',
          permissions: ['Administrador', 'Financeiro', 'Publico'],
          leftDrawer: NotasFiltrosDrawer,
          rightDrawer: NotasDetalhesDrawer,
        },
      },
      {
        path: 'nota/criar',
        name: 'nota-fiscal-create',
        component: () => import('pages/NotaFiscalFormPage.vue'),
        meta: {
          auth: true,
          title: 'Notas Fiscais',
          permissions: ['Administrador', 'Financeiro', 'Publico'],
        },
      },
      {
        path: 'nota/:codnotafiscal',
        name: 'nota-fiscal-view',
        component: () => import('pages/NotaFiscalViewPage.vue'),
        meta: {
          auth: true,
          title: 'Notas Fiscais',
          permissions: ['Administrador', 'Financeiro', 'Publico'],
        },
      },
      {
        path: 'nota/:codnotafiscal/editar',
        name: 'nota-fiscal-edit',
        component: () => import('pages/NotaFiscalFormPage.vue'),
        meta: {
          auth: true,
          title: 'Notas Fiscais',
          permissions: ['Administrador', 'Financeiro', 'Publico'],
        },
      },

      {
        path: 'nota/:codnotafiscal/item/:codnotafiscalitem/edit',
        name: 'nota-fiscal-item-edit',
        component: () => import('pages/NotaFiscalItem/NotaFiscalItemEditPage.vue'),
        meta: {
          auth: true,
          title: 'Notas Fiscais',
          permissions: ['Administrador', 'Financeiro', 'Publico'],
        },
      },
      {
        path: 'nota/:codnotafiscal/devolucao',
        name: 'nota-fiscal-devolucao',
        component: () => import('pages/NotaFiscalDevolucaoPage.vue'),
        meta: {
          auth: true,
          title: 'Notas Fiscais',
          permissions: ['Administrador', 'Financeiro', 'Publico'],
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
          title: 'Tributações Fiscais',
          leftDrawer: TributacaoFiltrosDrawer,
          rightDrawer: TributacaoSimuladorDrawer,
          permissions: ['Administrador', 'Contador'],
        },
      },
    ],
  },

  {
    path: '/cfop',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        name: 'cfop',
        component: () => import('pages/CfopListPage.vue'),
        meta: {
          auth: true,
          title: 'Listagem de CFOPs',
          leftDrawer: CfopFiltrosDrawer,
          permissions: ['Administrador', 'Contador'],
        },
      },
      {
        path: 'criar',
        name: 'cfop-create',
        component: () => import('pages/CfopFormPage.vue'),
        meta: {
          auth: true,
          title: 'Listagem de CFOPs',
          permissions: ['Administrador', 'Contador'],
        },
      },
      {
        path: ':codcfop/editar',
        name: 'cfop-edit',
        component: () => import('pages/CfopFormPage.vue'),
        meta: {
          auth: true,
          title: 'Listagem de CFOPs',
          permissions: ['Administrador', 'Contador'],
        },
      },
    ],
  },

  {
    path: '/cidade',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        name: 'cidade',
        component: () => import('pages/CidadeListPage.vue'),
        meta: {
          auth: true,
          title: 'Países, Estados e Cidades',
          leftDrawer: CidadeFiltrosDrawer,
          permissions: ['Administrador', 'Contador'],
        },
      },
    ],
  },

  {
    path: '/tributacao-cadastro',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        name: 'tributacao-cadastro',
        component: () => import('pages/TributacaoCadastroListPage.vue'),
        meta: {
          auth: true,
          title: 'Listagem de Tributações',
          leftDrawer: TributacaoCadastroFiltrosDrawer,
          permissions: ['Administrador', 'Contador'],
        },
      },
      {
        path: 'criar',
        name: 'tributacao-cadastro-create',
        component: () => import('pages/TributacaoCadastroFormPage.vue'),
        meta: {
          auth: true,
          title: 'Listagem de Tributações',
          permissions: ['Administrador', 'Contador'],
        },
      },
      {
        path: ':codtributacao/editar',
        name: 'tributacao-cadastro-edit',
        component: () => import('pages/TributacaoCadastroFormPage.vue'),
        meta: {
          auth: true,
          title: 'Listagem de Tributações',
          permissions: ['Administrador', 'Contador'],
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

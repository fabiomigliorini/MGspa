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
          title: 'Início',
        },
      },

      {
        path: 'patio',
        name: 'patio',
        component: () => import('pages/PatioPage.vue'),
        meta: { auth: true, title: 'Pátio de Recebimento' },
      },

      {
        path: 'safras',
        name: 'safras',
        component: () => import('pages/SafrasPage.vue'),
        meta: { auth: true, title: 'Safras' },
      },
      {
        path: 'safra/:codsafra',
        name: 'safra-detalhe',
        component: () => import('pages/SafraDetailPage.vue'),
        meta: { auth: true, title: 'Safra' },
      },
      {
        path: 'talhoes',
        name: 'talhoes',
        component: () => import('pages/TalhoesPage.vue'),
        meta: { auth: true, title: 'Talhões' },
      },
      {
        path: 'variedades',
        name: 'variedades',
        component: () => import('pages/VariedadesPage.vue'),
        meta: { auth: true, title: 'Variedades' },
      },
      {
        path: 'culturas',
        name: 'culturas',
        component: () => import('pages/CulturasPage.vue'),
        meta: { auth: true, title: 'Culturas' },
      },
      {
        path: 'tabela-desconto',
        name: 'tabela-desconto',
        component: () => import('pages/TabelaDescontoPage.vue'),
        meta: { auth: true, title: 'Tabela de Desconto' },
      },

      {
        path: 'contratos',
        name: 'contratos',
        component: () => import('pages/ContratosPage.vue'),
        meta: { auth: true, title: 'Contratos' },
      },
      {
        path: 'contrato/:codcontrato',
        name: 'contrato-detalhe',
        component: () => import('pages/ContratoDetailPage.vue'),
        meta: { auth: true, title: 'Contrato' },
      },
      {
        path: 'embarque',
        name: 'embarque',
        component: () => import('pages/EmbarquePage.vue'),
        meta: { auth: true, title: 'Pátio de Expedição' },
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

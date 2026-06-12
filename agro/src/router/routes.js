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
        path: 'fazendas',
        name: 'fazendas',
        component: () => import('pages/FazendasPage.vue'),
        meta: { auth: true, title: 'Fazendas' },
      },
      {
        path: 'fazenda/:codfazenda',
        name: 'fazenda-detalhe',
        component: () => import('pages/FazendaDetailPage.vue'),
        meta: { auth: true, title: 'Fazenda' },
      },
      {
        path: 'culturas',
        name: 'culturas',
        component: () => import('pages/CulturasPage.vue'),
        meta: { auth: true, title: 'Culturas' },
      },
      {
        path: 'cultura/:codcultura',
        name: 'cultura-detalhe',
        component: () => import('pages/CulturaDetailPage.vue'),
        meta: { auth: true, title: 'Cultura' },
      },
      {
        path: 'cultura/:codcultura/variedades',
        name: 'cultura-variedades',
        component: () => import('pages/CulturaVariedadesPage.vue'),
        meta: { auth: true, title: 'Variedades' },
      },
      {
        path: 'cultura/:codcultura/desconto',
        name: 'cultura-desconto',
        component: () => import('pages/CulturaDescontoPage.vue'),
        meta: { auth: true, title: 'Tabela de Desconto' },
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

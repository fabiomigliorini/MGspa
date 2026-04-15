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
          permissions: ['Administrador', 'Financeiro'],
        },
      },
      {
        path: 'extrato/:id/:mesAno',
        name: 'extrato',
        component: () => import('pages/MovimentacoesPage.vue'),
        meta: {
          auth: true,
          title: 'Extrato',
          permissions: ['Administrador', 'Financeiro'],
        },
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

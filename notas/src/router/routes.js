const routes = [
  {
    path: '/',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        name: 'home',
        component: () => import('pages/IndexPage.vue'),
        meta: { auth: true }  // <-- Requer autenticação
      },
      {
        path: 'admin',
        name: 'admin',
        component: () => import('pages/IndexPage.vue'), // Reutiliza por enquanto
        meta: {
          auth: true,
          permissions: ['Administrador']  // <-- Só admins
        }
      }
    ]
  },

  {
    path: '/login',
    name: 'login',
    component: () => import('pages/LoginPage.vue')
  },

  {
    path: '/:catchAll(.*)*',
    component: () => import('pages/ErrorNotFound.vue')
  }
]

export default routes
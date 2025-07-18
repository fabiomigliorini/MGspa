const routes = [
  // ROTAS LIVRES
  {
    path: "/login",
    name: "login",
    component: () => import("pages/Login.vue"),
  },
  // ROTAS AUTENTICADAS
  {
    path: '/',
    //component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: "",
        name: "inicio",
        component: () => import('pages/SaldosPage.vue'),

      },
      {
        path: "extrato/:id/:mesAno",
        name: "extrato",
        component: () => import('pages/MovimentacoesPage.vue')
      }
    ],
    meta: {
      auth: true,
    },
  },

  // Always leave this as last one,
  // but you can also remove it
  {
    path: '/:catchAll(.*)*',
    component: () => import('pages/ErrorNotFound.vue')
  }
]

export default routes

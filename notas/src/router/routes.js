const routes = [

  // ROTAS LIVRES
  {
    path: '/login', name: 'login',
    component: () => import('pages/Login.vue')
  },

  // ROTAS AUTENTICADAS
  {
    path: '/',
    children: [
      { path: '', name: 'inicio', component: () => import('pages/IndexPage.vue') },
    ],
    meta: {
      auth: true
    }
  },
  // Always leave this as last one,
  // but you can also remove it
  {
    path: '/:catchAll(.*)*',
    component: () => import('pages/ErrorNotFound.vue')
  },
]

export default routes


// const routes = [
//   {
//     path: '/',
//     component: () => import('pages/Login.vue'),
//     children: [
//       { path: '', component: () => import('pages/IndexPage.vue') }
//     ]
//   },

//   // Always leave this as last one,
//   // but you can also remove it
//   {
//     path: '/:catchAll(.*)*',
//     component: () => import('pages/ErrorNotFound.vue')
//   }
// ]

// export default routes

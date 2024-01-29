import router from '.'

const routes = [

  // ROTAS LIVRES
  {
    path: '/login', name: 'login',
    component: () => import('pages/Login.vue')
  },

  // ROTAS AUTENTICADAS
  {
    path: '/',
    // component: () => import('layouts/MGLayout.vue'),
    children: [
      { path: '', name: 'inicio', component: () => import('pages/pessoa/Index') },
      { path: '/pessoa', name: 'pessoa', component: () => import('pages/pessoa/Index') },
      { path: '/pessoa/nova', name: 'pessoanova', component: () => import('pages/pessoa/NovaPessoa') },
      { path: '/pessoa/:id', name: 'pessoaview', component: () => import('pages/pessoa/PessoaView') },
      { path: '/pessoa/edit/:id', name: 'pessoaedit', component: () => import('pages/pessoa/PessoaEditar') },
      { path: '/grupoeconomico', name:'grupoeconomicoindex', component: () => import('pages/GrupoEconomico/Index.vue') },
      { path: '/grupoeconomico/:id', name: 'grupoeconomico', component: () => import('pages/GrupoEconomico/GrupoEconomicoview') },
      { path: '/nota-fiscal/dashboard', name: 'notafiscaldash', component: () => import('pages/NotaFiscal/Index.vue') },
      { path: '/permissoes', name:'permissoes', component: () => import('pages/Permissoes/Index.vue') },

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

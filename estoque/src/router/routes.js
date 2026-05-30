import { PERMISSOES_ESTOQUE } from 'src/constants/permissoes'

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
          permissions: PERMISSOES_ESTOQUE,
        },
      },

      // ────────────────────────────────────────────────────────────────
      // Próximos CRUDs do app entram aqui, no padrão do contas:
      // cada rota com `auth: true`, `title`, `permissions` e, quando há
      // listagem com filtros, `leftDrawer: defineAsyncComponent(...)`.
      // ────────────────────────────────────────────────────────────────

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

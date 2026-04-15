import { PERMISSOES } from 'src/constants/permissoes'

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
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
        },
      },
      {
        path: 'extrato/:id/:mesAno',
        name: 'extrato',
        component: () => import('pages/MovimentacoesPage.vue'),
        meta: {
          auth: true,
          title: 'Extrato',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
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
    path: '/sem-permissao',
    name: 'sem-permissao',
    component: () => import('pages/SemPermissaoPage.vue'),
    meta: { auth: false, title: 'Sem permissão' },
  },

  {
    path: '/:catchAll(.*)*',
    name: 'error404',
    component: () => import('pages/ErrorNotFound.vue'),
  },
]

export default routes

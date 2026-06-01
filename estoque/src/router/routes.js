import { defineAsyncComponent } from 'vue'
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

      // ──────────────────────────── Marcas ────────────────────────────
      {
        path: 'marca',
        name: 'marca',
        component: () => import('pages/marca/Index.vue'),
        meta: {
          auth: true,
          title: 'Marcas',
          permissions: PERMISSOES_ESTOQUE,
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/MarcaFiltrosDrawer.vue'),
          ),
        },
      },
      {
        path: 'marca/:id',
        name: 'marca-detalhe',
        component: () => import('pages/marca/Detalhe.vue'),
        meta: { auth: true, title: 'Marca', permissions: PERMISSOES_ESTOQUE },
      },

      // ─────────────────────────── Etiquetas ──────────────────────────
      {
        path: 'etiqueta',
        name: 'etiqueta',
        component: () => import('pages/etiqueta/Index.vue'),
        meta: { auth: true, title: 'Etiquetas', permissions: PERMISSOES_ESTOQUE },
      },

      // ───────────────────── Conferência de Estoque ───────────────────
      {
        path: 'conferencia',
        name: 'conferencia',
        component: () => import('pages/conferencia/Setup.vue'),
        meta: { auth: true, title: 'Conferência de Estoque', permissions: PERMISSOES_ESTOQUE },
      },
      {
        path: 'conferencia/listagem/:codestoquelocal/:codmarca/:fiscal/:conferenciaperiodica/:data',
        name: 'conferencia-listagem',
        component: () => import('pages/conferencia/Listagem.vue'),
        meta: {
          auth: true,
          title: 'Conferência de Estoque',
          permissions: PERMISSOES_ESTOQUE,
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/ConferenciaFiltrosDrawer.vue'),
          ),
        },
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

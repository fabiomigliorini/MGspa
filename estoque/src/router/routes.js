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

      // ──────────────────────── Unidades de Medida ────────────────────
      {
        path: 'unidade-medida',
        name: 'unidade-medida',
        component: () => import('pages/unidade-medida/Index.vue'),
        meta: {
          auth: true,
          title: 'Unidades de Medida',
          permissions: PERMISSOES_ESTOQUE,
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/UnidadeMedidaFiltrosDrawer.vue'),
          ),
        },
      },

      // ────────────────────────── Tipos de Produto ────────────────────
      {
        path: 'tipo-produto',
        name: 'tipo-produto',
        component: () => import('pages/tipo-produto/Index.vue'),
        meta: {
          auth: true,
          title: 'Tipos de Produto',
          permissions: PERMISSOES_ESTOQUE,
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/TipoProdutoFiltrosDrawer.vue'),
          ),
        },
      },

      // ──────────────── Hierarquia de Produtos (árvore) ───────────────
      {
        path: 'secao-produto',
        name: 'secao-produto',
        component: () => import('pages/secao-produto/Index.vue'),
        meta: {
          auth: true,
          title: 'Hierarquia de Produtos',
          permissions: PERMISSOES_ESTOQUE,
        },
      },

      // ───────────────────────────── Produtos ─────────────────────────
      {
        path: 'produto',
        name: 'produto',
        component: () => import('pages/produto/Index.vue'),
        meta: {
          auth: true,
          title: 'Produtos',
          permissions: PERMISSOES_ESTOQUE,
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/ProdutoFiltrosDrawer.vue'),
          ),
        },
      },
      {
        path: 'produto/novo',
        name: 'produto-novo',
        component: () => import('pages/produto/Form.vue'),
        meta: { auth: true, title: 'Novo Produto', permissions: PERMISSOES_ESTOQUE },
      },
      {
        path: 'produto/:id/editar',
        name: 'produto-editar',
        component: () => import('pages/produto/Form.vue'),
        meta: { auth: true, title: 'Editar Produto', permissions: PERMISSOES_ESTOQUE },
      },
      {
        path: 'produto/:id',
        name: 'produto-detalhe',
        component: () => import('pages/produto/Detalhe.vue'),
        meta: { auth: true, title: 'Produto', permissions: PERMISSOES_ESTOQUE },
      },

      // ────────────────────────────── NCM ─────────────────────────────
      {
        path: 'ncm',
        name: 'ncm',
        component: () => import('pages/ncm/Index.vue'),
        meta: {
          auth: true,
          title: 'NCM',
          permissions: PERMISSOES_ESTOQUE,
        },
      },

      // ────────────────────── Saldos de Estoque (grid) ────────────────
      {
        path: 'estoque-saldo',
        name: 'estoque-saldo',
        component: () => import('pages/estoque-saldo/Index.vue'),
        meta: {
          auth: true,
          title: 'Saldos de Estoque',
          permissions: PERMISSOES_ESTOQUE,
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/EstoqueSaldoFiltrosDrawer.vue'),
          ),
        },
      },

      // ──────────────────────── Relatórios de Estoque ─────────────────
      {
        path: 'relatorios',
        name: 'relatorios',
        component: () => import('pages/relatorios/Index.vue'),
        meta: { auth: true, title: 'Relatórios', permissions: PERMISSOES_ESTOQUE },
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

import { defineAsyncComponent } from 'vue'
import { sincronizacaoStore } from 'stores/sincronizacao'

const drawer = {
  offlineEsquerdo: defineAsyncComponent(() => import('components/drawers/OfflineLeftDrawer.vue')),
  offlineDireito: defineAsyncComponent(() => import('components/drawers/OfflineRightDrawer.vue')),
  dispositivo: defineAsyncComponent(() => import('components/drawers/DispositivoLeftDrawer.vue')),
  listagem: defineAsyncComponent(() => import('components/drawers/ListagemLeftDrawer.vue')),
  valeModelo: defineAsyncComponent(() => import('components/drawers/ValeModeloLeftDrawer.vue')),
  valeEmitidos: defineAsyncComponent(() => import('components/drawers/ValeEmitidosLeftDrawer.vue')),
  confissao: defineAsyncComponent(() => import('components/drawers/ConfissaoLeftDrawer.vue')),
  pagamento: defineAsyncComponent(() => import('components/drawers/PagamentoLeftDrawer.vue')),
  woo: defineAsyncComponent(() => import('components/drawers/WooLeftDrawer.vue')),
}

const pdv = {
  title: 'PDV',
  leftDrawer: drawer.offlineEsquerdo,
  rightDrawer: drawer.offlineDireito,
}

const routes = [
  {
    path: '/',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      // OFFLINE
      { path: '', component: () => import('pages/IndexPage.vue'), meta: pdv },
      {
        path: '/offline/:uuid',
        name: 'offline',
        component: () => import('pages/IndexPage.vue'),
        meta: pdv,
      },
      {
        path: '/negocio/:codnegocio',
        name: 'negocio',
        component: () => import('pages/IndexPage.vue'),
        meta: pdv,
      },

      // DEVOLUCAO
      {
        path: '/offline/:uuid/devolucao',
        component: () => import('pages/DevolucaoPage.vue'),
        meta: { title: 'Devolução', backTo: (route) => '/offline/' + route.params.uuid },
      },

      // COMANDAS VENDEDOR
      {
        path: '/comanda-vendedor',
        component: () => import('pages/ComandaPage.vue'),
        meta: { title: 'Comanda de Vendedor', backTo: '/' },
      },

      // PAGARME
      {
        path: '/pagar-me',
        component: () => import('pages/PagarMePage.vue'),
        meta: { title: 'PagarMe', backTo: '/' },
      },

      // PRANCHETA
      {
        path: '/prancheta',
        component: () => import('pages/PranchetaPage.vue'),
        meta: { title: 'Prancheta', backTo: '/' },
      },

      // DISPOSITIVOS (PDVs)
      {
        path: '/dispositivo',
        component: () => import('pages/DispositivoPage.vue'),
        meta: { title: 'Dispositivos', leftDrawer: drawer.dispositivo },
      },
      // Meu Dispositivo: atalho para a pagina do dispositivo deste navegador (ou o Cadastrar)
      {
        path: '/dispositivo/meu',
        component: () => import('pages/DispositivoDetalhePage.vue'),
        meta: { title: 'Dispositivo', backTo: '/' },
      },
      {
        path: '/dispositivo/:codpdv(\\d+)',
        component: () => import('pages/DispositivoDetalhePage.vue'),
        meta: {
          title: 'Dispositivo',
          // o proprio dispositivo (Meu Dispositivo) volta para o PDV; os outros, para a lista
          backTo: (route) =>
            Number(route.params.codpdv) === sincronizacaoStore().pdv.codpdv ? '/' : '/dispositivo',
        },
      },

      // LISTAGEM NEGOCIOS
      {
        path: '/listagem',
        component: () => import('pages/ListagemPage.vue'),
        meta: { title: 'Listagem de Negócios', leftDrawer: drawer.listagem },
      },

      // MODELOS DE VALE COMPRAS
      {
        path: '/vale-modelo',
        component: () => import('pages/ValeModeloPage.vue'),
        meta: { title: 'Modelos de Vale', backTo: '/', leftDrawer: drawer.valeModelo },
      },
      {
        path: '/vale-modelo/emitidos',
        component: () => import('pages/ValeEmitidosPage.vue'),
        meta: { title: 'Vales Emitidos', backTo: '/vale-modelo', leftDrawer: drawer.valeEmitidos },
      },
      {
        path: '/vale-modelo/novo',
        name: 'valeModeloNovo',
        component: () => import('pages/ValeModeloFormPage.vue'),
        meta: { title: 'Modelo de Vale', backTo: '/vale-modelo' },
      },
      {
        path: '/vale-modelo/:codvalemodelo',
        name: 'valeModeloEditar',
        component: () => import('pages/ValeModeloFormPage.vue'),
        meta: { title: 'Modelo de Vale', backTo: '/vale-modelo' },
      },

      // CONFISSOES
      {
        path: '/confissao',
        component: () => import('pages/ConfissaoPage.vue'),
        meta: { title: 'Conferência das Confissões de Dívida', leftDrawer: drawer.confissao },
      },
      {
        path: '/confissao/faltando',
        component: () => import('pages/ConfissaoFaltandoPage.vue'),
        meta: { title: 'Conferência das Confissões de Dívida', leftDrawer: drawer.confissao },
      },

      // PAGAMENTOS
      {
        path: '/pagamento',
        component: () => import('pages/PagamentoPage.vue'),
        meta: { title: 'Pagamentos', backTo: '/', leftDrawer: drawer.pagamento },
      },
      {
        path: '/pagamento/receber',
        component: () => import('pages/PagamentoReceberPage.vue'),
        meta: { title: 'Receber Título / Pagar Vale', backTo: '/pagamento' },
      },
      {
        path: '/pagamento/pendentes',
        component: () => import('pages/PagamentoPendentesPage.vue'),
        meta: { title: 'Pagamentos não resolvidos', backTo: '/pagamento' },
      },

      // CAIXA
      {
        path: '/caixa',
        component: () => import('pages/CaixaPage.vue'),
        meta: { title: 'Caixa', backTo: '/' },
      },

      // WOOCOMMERCE
      {
        path: '/woo',
        component: () => import('pages/WooPage.vue'),
        meta: { title: 'Woo', backTo: '/woo/painel', leftDrawer: drawer.woo },
      },
      {
        path: '/woo/painel',
        component: () => import('pages/WooPainelPage.vue'),
        meta: { title: 'Woo' },
      },
    ],
  },

  // QUIOSQUE CONSULTA DE PRECOS: tela cheia, sem cabecalho
  {
    path: '/quiosque',
    component: () => import('layouts/QuiosqueLayout.vue'),
    children: [{ path: '', name: 'quiosque', component: () => import('pages/QuiosquePage.vue') }],
  },

  // ORCAMENTOS: impressao, sem layout
  {
    path: '/offline/:uuid/orcamento',
    children: [{ path: '', component: () => import('pages/OrcamentoPage.vue') }],
  },
  {
    path: '/offline/:uuid/orcamento-termica',
    children: [{ path: '', component: () => import('pages/OrcamentoTermicaPage.vue') }],
  },

  // Always leave this as last one,
  // but you can also remove it
  {
    path: '/:catchAll(.*)*',
    name: 'ErrorNotFound',
    component: () => import('pages/ErrorNotFound.vue'),
  },
]

export default routes

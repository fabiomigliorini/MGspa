import { defineAsyncComponent } from 'vue'
import { sincronizacaoStore } from 'stores/sincronizacao'
import { requisicaoStore } from 'stores/requisicao'
import { requisicaoGeradorStore } from 'stores/requisicaoGerador'

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
  requisicao: defineAsyncComponent(() => import('components/drawers/RequisicaoLeftDrawer.vue')),
}

// O rascunho do gerador de requisição é de uma loja só: com quantidade nele, outra loja no
// endereço é recusada (com o aviso) e volta para a dele
const geradorLoja = async (to) => {
  const sGerador = requisicaoGeradorStore()
  await sGerador.carregarLojas()
  if (sGerador.usarLoja(Number(to.params.codestoquelocal))) return true
  return `/requisicao/gerador/${sGerador.codestoquelocal}/marcas`
}
const marcasDoGerador = (route) => `/requisicao/gerador/${route.params.codestoquelocal}/marcas`

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

      // REQUISICOES ENTRE LOJAS (TASK-55)
      {
        path: '/requisicao',
        component: () => import('pages/RequisicaoListagemPage.vue'),
        meta: {
          title: 'Listagem de Requisições',
          leftDrawer: drawer.requisicao,
          backTo: () => requisicaoStore().enderecoPainel,
        },
      },
      {
        path: '/requisicao/painel/:codestoquelocal(\\d+)?',
        component: () => import('pages/RequisicaoPainelPage.vue'),
        meta: { title: 'Requisições', backTo: '/' },
      },
      {
        path: '/requisicao/nova',
        component: () => import('pages/RequisicaoFormPage.vue'),
        meta: { title: 'Nova Requisição', backTo: () => requisicaoStore().enderecoPainel },
      },
      {
        path: '/requisicao/:codrequisicao(\\d+)',
        component: () => import('pages/RequisicaoDetalhePage.vue'),
        meta: { title: 'Requisição', backTo: () => requisicaoStore().enderecoPainel },
      },
      {
        path: '/requisicao/:codrequisicao(\\d+)/editar',
        component: () => import('pages/RequisicaoFormPage.vue'),
        meta: {
          title: 'Editar Requisição',
          backTo: (route) => '/requisicao/' + route.params.codrequisicao,
        },
      },

      // GERADOR DE REQUISICAO (TASK-258)
      {
        path: '/requisicao/gerador',
        component: () => import('pages/RequisicaoGeradorPage.vue'),
        meta: { title: 'Gerador de Requisição', backTo: () => requisicaoStore().enderecoPainel },
      },
      {
        path: '/requisicao/gerador/:codestoquelocal(\\d+)/marcas',
        component: () => import('pages/RequisicaoGeradorMarcasPage.vue'),
        beforeEnter: geradorLoja,
        meta: { title: 'Gerador de Requisição', backTo: '/requisicao/gerador' },
      },
      {
        path: '/requisicao/gerador/:codestoquelocal(\\d+)/marca/:codmarca(\\d+)?',
        component: () => import('pages/RequisicaoGeradorProdutosPage.vue'),
        beforeEnter: geradorLoja,
        meta: { title: 'Gerador de Requisição', backTo: marcasDoGerador },
      },
      {
        path: '/requisicao/gerador/:codestoquelocal(\\d+)/produto/:codproduto(\\d+)',
        component: () => import('pages/RequisicaoGeradorProdutoPage.vue'),
        beforeEnter: geradorLoja,
        meta: {
          title: 'Gerador de Requisição',
          backTo: (route) => requisicaoGeradorStore().enderecoLista || marcasDoGerador(route),
        },
      },
      {
        path: '/requisicao/gerador/:codestoquelocal(\\d+)/revisao',
        component: () => import('pages/RequisicaoGeradorRevisaoPage.vue'),
        beforeEnter: geradorLoja,
        meta: {
          title: 'Gerador de Requisição',
          backTo: (route) => requisicaoGeradorStore().enderecoLista || marcasDoGerador(route),
        },
      },

      // ENTREGAS DO NEGOCIO (TASK-55)
      {
        path: '/entrega',
        component: () => import('pages/EntregaPage.vue'),
        meta: { title: 'Entregas', backTo: '/' },
      },

      // IMPRESSORAS (TASK-255)
      {
        path: '/impressora',
        component: () => import('pages/ImpressoraPage.vue'),
        meta: { title: 'Impressoras', backTo: '/' },
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
        path: '/pagamento/:id(\\d+)',
        name: 'pagamento-detalhe',
        component: () => import('pages/PagamentoDetalhePage.vue'),
        meta: { title: 'Pagamento', backTo: '/pagamento' },
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

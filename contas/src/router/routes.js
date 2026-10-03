import { defineAsyncComponent } from 'vue'
import { PERMISSOES } from 'src/constants/permissoes'

const routes = [
  {
    path: '/',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        redirect: { name: 'pix' },
      },
      {
        path: 'banco',
        name: 'banco',
        component: () => import('pages/banco/Index.vue'),
        meta: {
          auth: true,
          title: 'Bancos',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/BancoFiltrosDrawer.vue'),
          ),
        },
      },
      {
        path: 'moeda',
        name: 'moeda',
        component: () => import('pages/moeda/Index.vue'),
        meta: {
          auth: true,
          title: 'Moedas',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/MoedaFiltrosDrawer.vue'),
          ),
        },
      },
      {
        path: 'conta-contabil',
        name: 'conta-contabil',
        component: () => import('pages/contaContabil/Index.vue'),
        meta: {
          auth: true,
          title: 'Contas Contábeis',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/ContaContabilFiltrosDrawer.vue'),
          ),
        },
      },
      {
        path: 'unidade-referencia',
        name: 'unidade-referencia',
        component: () => import('pages/unidadeReferencia/Index.vue'),
        meta: {
          auth: true,
          title: 'Unidades de Referência (UPF)',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/UnidadeReferenciaFiltrosDrawer.vue'),
          ),
        },
      },
      {
        path: 'unidade-referencia/:codunidadereferencia(\\d+)',
        name: 'unidade-referencia-detalhe',
        component: () => import('pages/unidadeReferencia/Detalhe.vue'),
        meta: {
          auth: true,
          title: 'Unidade de Referência',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
        },
      },
      {
        path: 'tipo-movimento-titulo',
        name: 'tipo-movimento-titulo',
        component: () => import('pages/tipoMovimentoTitulo/Index.vue'),
        meta: {
          auth: true,
          title: 'Tipos de Movimentos de Títulos',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/TipoMovimentoTituloFiltrosDrawer.vue'),
          ),
        },
      },
      {
        path: 'tipo-titulo',
        name: 'tipo-titulo',
        component: () => import('pages/tipoTitulo/Index.vue'),
        meta: {
          auth: true,
          title: 'Tipos de Títulos',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/TipoTituloFiltrosDrawer.vue'),
          ),
        },
      },
      {
        // painel dos portadores (doc-4): substitui Saldos e Cadastros → Portadores
        path: 'portador',
        name: 'portador',
        component: () => import('pages/portador/Index.vue'),
        meta: {
          auth: true,
          title: 'Portadores',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO, PERMISSOES.GERENTE],
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/PortadorFiltrosDrawer.vue'),
          ),
        },
      },
      {
        // o portador e o período (doc-4)
        path: 'portador/:codportador(\\d+)/:codportadorperiodo(\\d+)?',
        name: 'portador-detalhe',
        component: () => import('pages/portador/Detalhe.vue'),
        meta: {
          auth: true,
          title: 'Portador',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO, PERMISSOES.GERENTE],
        },
      },
      {
        path: 'maquineta',
        name: 'maquineta',
        component: () => import('pages/maquineta/Index.vue'),
        meta: {
          auth: true,
          title: 'Maquinetas',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO, PERMISSOES.GERENTE],
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/MaquinetaFiltrosDrawer.vue'),
          ),
        },
      },
      {
        path: 'caixa-item',
        name: 'caixa-item',
        component: () => import('pages/caixaItem/Index.vue'),
        meta: {
          auth: true,
          title: 'Itens do Caixa',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
        },
      },
      {
        path: 'maquineta/:id(\\d+)/lotes',
        name: 'maquineta-lotes',
        component: () => import('pages/maquineta/Lotes.vue'),
        meta: {
          auth: true,
          title: 'Lotes da Maquineta',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO, PERMISSOES.GERENTE],
        },
      },
      {
        path: 'caixa',
        name: 'caixa',
        component: () => import('pages/caixa/Index.vue'),
        meta: {
          auth: true,
          title: 'Movimento dos Itens do Caixa',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO, PERMISSOES.GERENTE],
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/CaixaFiltrosDrawer.vue'),
          ),
        },
      },
      {
        path: 'fechamento',
        name: 'fechamento',
        component: () => import('pages/fechamento/Index.vue'),
        meta: {
          auth: true,
          title: 'Fechamentos',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO, PERMISSOES.GERENTE],
        },
      },
      {
        path: 'fechamento/lote/:id(\\d+)',
        name: 'fechamento-lote',
        component: () => import('pages/fechamento/Lote.vue'),
        meta: {
          auth: true,
          title: 'Lote da Maquineta',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO, PERMISSOES.GERENTE],
        },
      },
      {
        path: 'fechamento/sessao/:id(\\d+)',
        name: 'fechamento-sessao',
        component: () => import('pages/fechamento/Sessao.vue'),
        meta: {
          auth: true,
          title: 'Caixa',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO, PERMISSOES.GERENTE],
        },
      },
      {
        path: 'fechamento/venda/:id(\\d+)',
        name: 'fechamento-venda',
        component: () => import('pages/fechamento/Venda.vue'),
        meta: {
          auth: true,
          title: 'Venda com Diferença',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO, PERMISSOES.GERENTE],
        },
      },
      {
        path: 'forma-pagamento',
        name: 'forma-pagamento',
        component: () => import('pages/formaPagamento/Index.vue'),
        meta: {
          auth: true,
          title: 'Formas de Pagamento',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/FormaPagamentoFiltrosDrawer.vue'),
          ),
        },
      },
      {
        path: 'pix',
        name: 'pix',
        component: () => import('pages/pix/Index.vue'),
        meta: {
          auth: true,
          title: 'Pix',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO, PERMISSOES.CAIXA],
          leftDrawer: defineAsyncComponent(() => import('components/drawers/PixFiltrosDrawer.vue')),
        },
      },
      {
        path: 'portador/:codportador/extrato/:ano(\\d{4})/:mes(\\d{2})',
        name: 'extrato',
        component: () => import('pages/ExtratoPage.vue'),
        meta: {
          auth: true,
          title: 'Extrato',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/ExtratoFiltrosDrawer.vue'),
          ),
        },
      },
      {
        path: 'boleto/abertos',
        name: 'boleto-abertos',
        component: () => import('pages/boleto/AbertosPage.vue'),
        meta: {
          auth: true,
          title: 'Boletos Abertos',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO, PERMISSOES.COBRANCA],
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/BoletoAbertosDrawer.vue'),
          ),
        },
      },
      {
        path: 'boleto/liquidados/:ano(\\d{4})?/:mes(\\d{2})?/:dia(\\d{2})?/:codportador(\\d+)?',
        name: 'boleto-liquidados',
        component: () => import('pages/boleto/LiquidadosPage.vue'),
        meta: {
          auth: true,
          title: 'Boletos Liquidados',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO, PERMISSOES.COBRANCA],
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/BoletoLiquidadosDrawer.vue'),
          ),
        },
      },
      {
        path: 'boleto/baixados',
        name: 'boleto-baixados',
        component: () => import('pages/boleto/BaixadosPage.vue'),
        meta: {
          auth: true,
          title: 'Boletos Baixados',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO, PERMISSOES.COBRANCA],
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/BoletoBaixadosDrawer.vue'),
          ),
        },
      },
      {
        path: 'titulo',
        name: 'titulo',
        component: () => import('pages/titulo/Index.vue'),
        meta: {
          auth: true,
          title: 'Títulos',
          // Visualização: qualquer usuário autenticado
          permissions: [
            PERMISSOES.ADMINISTRADOR,
            PERMISSOES.FINANCEIRO,
            PERMISSOES.COBRANCA,
            PERMISSOES.PUBLICO,
            PERMISSOES.CAIXA,
          ],
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/TituloFiltrosDrawer.vue'),
          ),
        },
      },
      {
        path: 'titulo/novo',
        name: 'titulo-novo',
        component: () => import('pages/titulo/Novo.vue'),
        meta: {
          auth: true,
          title: 'Novo Título',
          // Criação: apenas financeiro/cobrança/admin
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO, PERMISSOES.COBRANCA],
        },
      },
      {
        path: 'titulo/:codtitulo(\\d+)',
        name: 'titulo-detalhe',
        component: () => import('pages/titulo/Detalhe.vue'),
        meta: {
          auth: true,
          title: 'Título',
          // Visualização: qualquer usuário autenticado
          permissions: [
            PERMISSOES.ADMINISTRADOR,
            PERMISSOES.FINANCEIRO,
            PERMISSOES.COBRANCA,
            PERMISSOES.PUBLICO,
            PERMISSOES.CAIXA,
          ],
        },
      },
      {
        path: 'pagamento',
        name: 'pagamento',
        component: () => import('pages/pagamento/Index.vue'),
        meta: {
          auth: true,
          title: 'Pagamentos',
          permissions: [
            PERMISSOES.ADMINISTRADOR,
            PERMISSOES.FINANCEIRO,
            PERMISSOES.COBRANCA,
            PERMISSOES.GERENTE,
            PERMISSOES.CAIXA,
          ],
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/PagamentoFiltrosDrawer.vue'),
          ),
        },
      },
      {
        path: 'pagamento/novo',
        name: 'pagamento-novo',
        component: () => import('pages/pagamento/Nova.vue'),
        meta: {
          auth: true,
          title: 'Receber ou Pagar Títulos',
          permissions: [
            PERMISSOES.ADMINISTRADOR,
            PERMISSOES.FINANCEIRO,
            PERMISSOES.COBRANCA,
            PERMISSOES.GERENTE,
            PERMISSOES.CAIXA,
          ],
        },
      },
      {
        path: 'pagamento/:id(\\d+)',
        name: 'pagamento-detalhe',
        component: () => import('pages/pagamento/Detalhe.vue'),
        meta: {
          auth: true,
          title: 'Pagamento',
          permissions: [
            PERMISSOES.ADMINISTRADOR,
            PERMISSOES.FINANCEIRO,
            PERMISSOES.COBRANCA,
            PERMISSOES.GERENTE,
            PERMISSOES.CAIXA,
          ],
        },
      },
      {
        path: 'agrupamento',
        name: 'agrupamento',
        component: () => import('pages/tituloAgrupamento/Index.vue'),
        meta: {
          auth: true,
          title: 'Agrupamentos de Títulos',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO, PERMISSOES.COBRANCA],
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/TituloAgrupamentoFiltrosDrawer.vue'),
          ),
        },
      },
      {
        path: 'agrupamento/pendentes',
        name: 'agrupamento-pendentes',
        component: () => import('pages/tituloAgrupamento/Pendentes.vue'),
        meta: {
          auth: true,
          title: 'Fechamentos Pendentes',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO, PERMISSOES.COBRANCA],
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/AgrupamentoPendentesFiltrosDrawer.vue'),
          ),
        },
      },
      {
        path: 'agrupamento/novo',
        name: 'agrupamento-novo',
        component: () => import('pages/tituloAgrupamento/Novo.vue'),
        meta: {
          auth: true,
          title: 'Novo Agrupamento',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO, PERMISSOES.COBRANCA],
        },
      },
      {
        path: 'agrupamento/:id(\\d+)',
        name: 'agrupamento-detalhe',
        component: () => import('pages/tituloAgrupamento/Detalhe.vue'),
        meta: {
          auth: true,
          title: 'Agrupamento',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO, PERMISSOES.COBRANCA],
        },
      },
      {
        path: 'cheque',
        name: 'cheque',
        component: () => import('pages/cheque/Index.vue'),
        meta: {
          auth: true,
          title: 'Cheques',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/ChequeFiltrosDrawer.vue'),
          ),
        },
      },
      {
        path: 'cheque/novo',
        name: 'cheque-novo',
        component: () => import('pages/cheque/Form.vue'),
        meta: {
          auth: true,
          title: 'Novo Cheque',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
        },
      },
      {
        path: 'cheque/:codcheque(\\d+)',
        name: 'cheque-detalhe',
        component: () => import('pages/cheque/Detalhe.vue'),
        meta: {
          auth: true,
          title: 'Cheque',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
        },
      },
      {
        path: 'cheque/:codcheque(\\d+)/editar',
        name: 'cheque-editar',
        component: () => import('pages/cheque/Form.vue'),
        meta: {
          auth: true,
          title: 'Editar Cheque',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
        },
      },
      {
        path: 'cheque-repasse',
        name: 'cheque-repasse',
        component: () => import('pages/chequeRepasse/Index.vue'),
        meta: {
          auth: true,
          title: 'Repasses de Cheques',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/ChequeRepasseFiltrosDrawer.vue'),
          ),
        },
      },
      {
        path: 'cheque-repasse/novo',
        name: 'cheque-repasse-novo',
        component: () => import('pages/chequeRepasse/Novo.vue'),
        meta: {
          auth: true,
          title: 'Novo Repasse',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
        },
      },
      {
        path: 'cheque-repasse/:codchequerepasse(\\d+)',
        name: 'cheque-repasse-detalhe',
        component: () => import('pages/chequeRepasse/Detalhe.vue'),
        meta: {
          auth: true,
          title: 'Repasse',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
        },
      },
      {
        path: 'cheque-motivo-devolucao',
        name: 'cheque-motivo-devolucao',
        component: () => import('pages/chequeMotivoDevolucao/Index.vue'),
        meta: {
          auth: true,
          title: 'Motivos de Devolução de Cheque',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/ChequeMotivoDevolucaoFiltrosDrawer.vue'),
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

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
        path: 'portador/saldos/:dia(\\d{4}-\\d{2}-\\d{2})?',
        name: 'portador-saldos',
        component: () => import('pages/SaldosPage.vue'),
        meta: {
          auth: true,
          title: 'Saldos',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/SaldosFiltrosDrawer.vue'),
          ),
        },
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
        path: 'portador',
        name: 'portador',
        component: () => import('pages/portador/Index.vue'),
        meta: {
          auth: true,
          title: 'Portadores',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO],
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/PortadorFiltrosDrawer.vue'),
          ),
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
        path: 'liquidacao-titulo',
        name: 'liquidacao-titulo',
        component: () => import('pages/liquidacaoTitulo/Index.vue'),
        meta: {
          auth: true,
          title: 'Liquidações de Títulos',
          permissions: [
            PERMISSOES.ADMINISTRADOR,
            PERMISSOES.FINANCEIRO,
            PERMISSOES.COBRANCA,
            PERMISSOES.GERENTE,
            PERMISSOES.CAIXA,
          ],
          leftDrawer: defineAsyncComponent(
            () => import('components/drawers/LiquidacaoTituloFiltrosDrawer.vue'),
          ),
        },
      },
      {
        path: 'liquidacao-titulo/nova',
        name: 'liquidacao-titulo-nova',
        component: () => import('pages/liquidacaoTitulo/Nova.vue'),
        meta: {
          auth: true,
          title: 'Nova Liquidação',
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
        path: 'liquidacao-titulo/:id(\\d+)',
        name: 'liquidacao-titulo-detalhe',
        component: () => import('pages/liquidacaoTitulo/Detalhe.vue'),
        meta: {
          auth: true,
          title: 'Liquidação',
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

import { markRaw, defineAsyncComponent } from 'vue'
import { PERMISSOES } from 'src/constants/permissoes'

// Drawers do pátio: o MainLayout renderiza `meta.leftDrawer`/`meta.rightDrawer`
// como componente. markRaw evita o Vue tornar o componente reativo via $route.
const CargaLeftDrawer = markRaw(
  defineAsyncComponent(() => import('components/carga/CargaLeftDrawer.vue')),
)
const CargaResumo = markRaw(defineAsyncComponent(() => import('components/carga/CargaResumo.vue')))

// Filtros da listagem de romaneios (tela de consulta, separada do pátio).
const CargasFiltrosDrawer = markRaw(
  defineAsyncComponent(() => import('components/cargas/CargasFiltrosDrawer.vue')),
)

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
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.GERENTE],
        },
      },

      {
        // carga/:uuid abre a carga; carga/nova registra uma; sem uuid = só listagem.
        path: 'carga/:uuid?',
        name: 'carga',
        component: () => import('pages/CargaPage.vue'),
        meta: {
          auth: true,
          title: 'Pátio de Cargas',
          leftDrawer: CargaLeftDrawer,
          rightDrawer: CargaResumo,
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.GERENTE],
        },
      },
      {
        // Consulta do histórico — plural, pra não colidir com o pátio
        // (`carga/:uuid`), que é a tela de operação e continua offline-first.
        path: 'cargas',
        name: 'cargas',
        component: () => import('pages/CargasPage.vue'),
        meta: {
          auth: true,
          title: 'Romaneios',
          leftDrawer: CargasFiltrosDrawer,
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.GERENTE],
        },
      },
      {
        path: 'cargas/:codcarga',
        name: 'carga-detalhe',
        component: () => import('pages/CargaDetailPage.vue'),
        meta: {
          auth: true,
          title: 'Romaneio',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.GERENTE],
        },
      },
      {
        path: 'extrato',
        name: 'extrato',
        component: () => import('pages/ExtratoPage.vue'),
        meta: {
          auth: true,
          title: 'Estoque & Extrato',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.GERENTE],
        },
      },
      {
        path: 'unidades-armazenadoras',
        name: 'unidades-armazenadoras',
        component: () => import('pages/UnidadesArmazenadorasPage.vue'),
        meta: {
          auth: true,
          title: 'Unidades Armazenadoras',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.GERENTE],
        },
      },

      {
        path: 'safras',
        name: 'safras',
        component: () => import('pages/SafrasPage.vue'),
        meta: {
          auth: true,
          title: 'Safras',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.GERENTE],
        },
      },
      {
        path: 'safra/:codsafra',
        name: 'safra-detalhe',
        component: () => import('pages/SafraDetailPage.vue'),
        meta: {
          auth: true,
          title: 'Safra',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.GERENTE],
        },
      },
      {
        path: 'safra/:codsafra/plantio/:codplantio',
        name: 'plantio-detalhe',
        component: () => import('pages/PlantioDetailPage.vue'),
        meta: {
          auth: true,
          title: 'Plantio',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.GERENTE],
        },
      },
      {
        path: 'fazendas',
        name: 'fazendas',
        component: () => import('pages/FazendasPage.vue'),
        meta: {
          auth: true,
          title: 'Fazendas',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.GERENTE],
        },
      },
      {
        path: 'fazenda/:codfazenda',
        name: 'fazenda-detalhe',
        component: () => import('pages/FazendaDetailPage.vue'),
        meta: {
          auth: true,
          title: 'Fazenda',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.GERENTE],
        },
      },
      {
        path: 'culturas',
        name: 'culturas',
        component: () => import('pages/CulturasPage.vue'),
        meta: {
          auth: true,
          title: 'Culturas',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.GERENTE],
        },
      },
      {
        path: 'cultura/:codcultura',
        name: 'cultura-detalhe',
        component: () => import('pages/CulturaDetailPage.vue'),
        meta: {
          auth: true,
          title: 'Cultura',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.GERENTE],
        },
      },
      {
        path: 'cultura/:codcultura/variedades',
        name: 'cultura-variedades',
        component: () => import('pages/CulturaVariedadesPage.vue'),
        meta: {
          auth: true,
          title: 'Variedades',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.GERENTE],
        },
      },
      {
        // Path e name preservados: o cadastro de classificação é o mesmo lugar
        // de sempre (Cultura › Classificação), só que agora é UMA tela — os
        // parâmetros carregam a fórmula, não há mais tabela intermediária.
        path: 'cultura/:codcultura/desconto',
        name: 'cultura-desconto',
        component: () => import('pages/ParametroClassificacaoPage.vue'),
        meta: {
          auth: true,
          title: 'Classificação',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.GERENTE],
        },
      },

      {
        path: 'contrato/:codcontrato',
        name: 'contrato-detalhe',
        component: () => import('pages/ContratoDetailPage.vue'),
        meta: {
          auth: true,
          title: 'Contrato',
          permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.GERENTE],
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

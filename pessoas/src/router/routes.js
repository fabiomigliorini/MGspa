const routes = [
  // ROTAS LIVRES
  {
    path: '/login',
    name: 'login',
    component: () => import('pages/Login.vue'),
  },

  // ROTAS AUTENTICADAS
  {
    path: '/',
    // component: () => import('layouts/MGLayout.vue'),
    children: [
      {
        path: '',
        name: 'inicio',
        component: () => import('pages/Aniversarios/Index.vue'),
      },
      {
        path: '/pessoa',
        name: 'pessoa',
        component: () => import('pages/pessoa/Index.vue'),
      },
      {
        path: '/pessoa/nova',
        name: 'pessoanova',
        component: () => import('pages/pessoa/NovaPessoa.vue'),
      },
      {
        path: '/pessoa/:id',
        name: 'pessoaView',
        component: () => import('pages/pessoa/PessoaView.vue'),
      },

      {
        path: '/grupoeconomico',
        name: 'grupoeconomicoindex',
        component: () => import('pages/GrupoEconomico/Index.vue'),
      },
      {
        path: '/grupoeconomico/:id',
        name: 'grupoeconomico',
        component: () => import('pages/GrupoEconomico/GrupoEconomicoView.vue'),
      },

      {
        path: '/usuarios',
        name: 'usuarios',
        component: () => import('pages/usuarios/Index.vue'),
      },
      {
        path: '/usuarios/:codusuario',
        name: 'usuariosview',
        component: () => import('pages/usuarios/usuariosview.vue'),
      },
      {
        path: '/usuarios/:codusuario/editar',
        name: 'usuariosedit',
        component: () => import('pages/usuarios/usuarioseditar.vue'),
      },
      {
        path: '/perfil',
        name: 'perfil',
        component: () => import('pages/usuarios/perfil.vue'),
      },
      {
        path: '/usuarios/novo',
        name: 'usuarionovo',
        component: () => import('pages/usuarios/novo.vue'),
      },

      {
        path: '/grupo-usuarios',
        name: 'grupousuarios',
        component: () => import('pages/grupo-usuario/Index.vue'),
      },
      {
        path: '/grupo-usuarios/:codgrupousuario',
        name: 'grupousuarioview',
        component: () => import('pages/grupo-usuario/grupoUsuarioView.vue'),
      },

      {
        path: '/ferias/:ano',
        name: 'feriasindex',
        component: () => import('pages/Ferias/Index.vue'),
      },
      {
        path: '/cargo',
        name: 'cargosindex',
        component: () => import('pages/Cargos/Index.vue'),
      },
      {
        path: '/cargo/:id',
        name: 'cargoView',
        component: () => import('pages/Cargos/cargoView.vue'),
      },
      {
        path: '/aniversarios',
        name: 'aniversariosindex',
        component: () => import('pages/Aniversarios/Index.vue'),
      },
      {
        path: '/comissao-caixas',
        name: 'comissaocaixas',
        component: () => import('pages/Cargos/ComissaoCaixas.vue'),
      },
      // Feriado
      {
        path: '/feriado',
        name: 'feriadoIndex',
        component: () => import('pages/feriado/Index.vue'),
      },

      // Unidade de Negócio
      {
        path: '/unidade-negocio',
        name: 'unidadeNegocioIndex',
        component: () => import('pages/unidade-negocio/Index.vue'),
      },

      // Meta
      {
        path: '/meta',
        name: 'metaIndex',
        component: () => import('pages/meta/Index.vue'),
      },
      {
        path: '/meta/:codmeta/dashboard',
        name: 'metaDashboard',
        component: () => import('pages/meta/MetaDashboard.vue'),
      },
      {
        path: '/meta/:codmeta/dashboard/:codpessoa',
        name: 'metaDashboardColaborador',
        component: () => import('pages/meta/MetaColaboradorDashboard.vue'),
      },
      {
        path: '/meta/:codmeta/unidade/:codunidadenegocio',
        name: 'metaUnidadeDashboard',
        component: () => import('pages/meta/MetaUnidadeDashboard.vue'),
      },
      {
        path: '/etnia',
        name: 'etnia',
        component: () => import('pages/etnia/Index.vue'),
      },
      {
        path: '/grau-instrucao',
        name: 'grauinstrucao',
        component: () => import('pages/grau-instrucao/Index.vue'),
      },
      {
        path: '/estado-civil',
        name: 'estadocivil',
        component: () => import('pages/estado-civil/Index.vue'),
      },
      {
        path: '/grupo-cliente',
        name: 'grupocliente',
        component: () => import('pages/grupo-cliente/Index.vue'),
      },

      // Certidão Emissor
      {
        path: '/certidao-emissor',
        name: 'certidaoemissor',
        component: () => import('pages/certidao-emissor/Index.vue'),
      },

      // RH — Meu Painel (qualquer usuário autenticado)
      {
        path: '/rh/meu-painel',
        name: 'rhMeuPainelIndex',
        component: () => import('pages/rh/MeuPainelIndex.vue'),
        children: [
          {
            path: ':codperiodo',
            name: 'rhMeuPainelDashboard',
            component: () => import('pages/rh/MeuPainelDashboard.vue'),
          },
          {
            path: ':codperiodo/colaborador/:codperiodocolaborador',
            name: 'rhMeuPainelColaborador',
            component: () => import('pages/rh/MeuPainelColaborador.vue'),
          },
          {
            path: ':codperiodo/indicador/:codindicador',
            name: 'rhMeuPainelExtrato',
            component: () => import('pages/rh/IndicadorExtrato.vue'),
          },
        ],
      },

      // RH — Metas & Variáveis (admin)
      {
        path: '/rh',
        name: 'rhIndex',
        component: () => import('pages/rh/Index.vue'),
        children: [
          {
            path: ':codperiodo',
            name: 'rhDashboard',
            component: () => import('pages/rh/PeriodoDashboard.vue'),
          },
          {
            path: ':codperiodo/colaborador/:codperiodocolaborador',
            name: 'rhColaboradorDetalhe',
            component: () => import('pages/rh/ColaboradorDetalhe.vue'),
          },
          {
            path: ':codperiodo/indicador/:codindicador',
            name: 'rhIndicadorExtrato',
            component: () => import('pages/rh/IndicadorExtrato.vue'),
          },
        ],
      },

      // Empresa
      {
        path: '/empresa',
        name: 'empresa',
        component: () => import('pages/empresa/Index.vue'),
      },
      {
        path: '/empresa/nova',
        name: 'empresanova',
        component: () => import('pages/empresa/NovaEmpresa.vue'),
      },
      {
        path: '/empresa/:codempresa',
        name: 'empresaview',
        component: () => import('pages/empresa/EmpresaView.vue'),
      },
      {
        path: '/empresa/:codempresa/editar',
        name: 'empresaeditar',
        component: () => import('pages/empresa/EmpresaEditar.vue'),
      },
      {
        path: '/empresa/:codempresa/filial/nova',
        name: 'filialNova',
        component: () => import('pages/empresa/NovaFilial.vue'),
      },
      {
        path: '/filial/:codfilial',
        name: 'filialview',
        component: () => import('pages/empresa/FilialView.vue'),
      },
      {
        path: '/filial/:codfilial/editar',
        name: 'filialeditar',
        component: () => import('pages/empresa/FilialEditar.vue'),
      },
    ],
    meta: {
      auth: true,
    },
  },

  // Always leave this as last one,
  // but you can also remove it
  {
    path: '/:catchAll(.*)*',
    component: () => import('pages/ErrorNotFound.vue'),
  },
]

export default routes

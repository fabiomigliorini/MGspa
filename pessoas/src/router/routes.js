import router from ".";

const routes = [
  // ROTAS LIVRES
  {
    path: "/login",
    name: "login",
    component: () => import("pages/Login.vue"),
  },

  // ROTAS AUTENTICADAS
  {
    path: "/",
    // component: () => import('layouts/MGLayout.vue'),
    children: [
      {
        path: "",
        name: "inicio",
        component: () => import("pages/Aniversarios/Index.vue"),
      },
      {
        path: "/pessoa",
        name: "pessoa",
        component: () => import("pages/pessoa/Index"),
      },
      {
        path: "/pessoa/nova",
        name: "pessoanova",
        component: () => import("pages/pessoa/NovaPessoa"),
      },
      {
        path: "/pessoa/:id",
        name: "pessoaView",
        component: () => import("pages/pessoa/PessoaView"),
      },
      {
        path: "/pessoa/edit/:id",
        name: "pessoaedit",
        component: () => import("pages/pessoa/PessoaEditar"),
      },

      {
        path: "/grupoeconomico",
        name: "grupoeconomicoindex",
        component: () => import("pages/GrupoEconomico/Index.vue"),
      },
      {
        path: "/grupoeconomico/:id",
        name: "grupoeconomico",
        component: () => import("pages/GrupoEconomico/GrupoEconomicoView"),
      },

      {
        path: "/usuarios",
        name: "usuarios",
        component: () => import("pages/usuarios/Index.vue"),
      },
      {
        path: "/usuarios/:codusuario",
        name: "usuariosview",
        component: () => import("pages/usuarios/usuariosview"),
      },
      {
        path: "/usuarios/:codusuario/editar",
        name: "usuariosedit",
        component: () => import("pages/usuarios/usuarioseditar"),
      },
      {
        path: "/perfil",
        name: "perfil",
        component: () => import("pages/usuarios/perfil"),
      },
      {
        path: "/usuarios/novo",
        name: "usuarionovo",
        component: () => import("pages/usuarios/novo"),
      },

      {
        path: "/grupo-usuarios",
        name: "grupousuarios",
        component: () => import("pages/grupo-usuario/Index.vue"),
      },
      {
        path: "/grupo-usuarios/:codgrupousuario",
        name: "grupousuarioview",
        component: () => import("pages/grupo-usuario/grupoUsuarioView"),
      },

      {
        path: "/ferias/:ano",
        name: "feriasindex",
        component: () => import("pages/Ferias/Index.vue"),
      },
      {
        path: "/cargo",
        name: "cargosindex",
        component: () => import("pages/Cargos/Index.vue"),
      },
      {
        path: "/cargo/:id",
        name: "cargoView",
        component: () => import("pages/Cargos/cargoView.vue"),
      },
      {
        path: "/aniversarios",
        name: "aniversariosindex",
        component: () => import("pages/Aniversarios/Index.vue"),
      },
      {
        path: "/comissao-caixas",
        name: "comissaocaixas",
        component: () => import("pages/Cargos/ComissaoCaixas.vue"),
      },
      {
        path: "/metas/:codmeta?",
        name: "metas",
        component: () => import("pages/Metas/Index.vue"),
      },
      {
        path: "/etnia",
        name: "etnia",
        component: () => import("pages/etnia/Index.vue"),
      },
      {
        path: "/grau-instrucao",
        name: "grauinstrucao",
        component: () => import("pages/grau-instrucao/Index.vue"),
      },
      {
        path: "/estado-civil",
        name: "estadocivil",
        component: () => import("pages/estado-civil/Index.vue"),
      },

      // Empresa
      {
        path: "/empresa",
        name: "empresa",
        component: () => import("pages/empresa/Index.vue"),
      },
      {
        path: "/empresa/nova",
        name: "empresanova",
        component: () => import("pages/empresa/NovaEmpresa.vue"),
      },
      {
        path: "/empresa/:codempresa",
        name: "empresaview",
        component: () => import("pages/empresa/EmpresaView.vue"),
      },
      {
        path: "/empresa/:codempresa/editar",
        name: "empresaeditar",
        component: () => import("pages/empresa/EmpresaEditar.vue"),
      },
      {
        path: "/empresa/:codempresa/filial/nova",
        name: "filialNova",
        component: () => import("pages/empresa/NovaFilial.vue"),
      },
      {
        path: "/filial/:codfilial",
        name: "filialview",
        component: () => import("pages/empresa/FilialView.vue"),
      },
      {
        path: "/filial/:codfilial/editar",
        name: "filialeditar",
        component: () => import("pages/empresa/FilialEditar.vue"),
      },
    ],
    meta: {
      auth: true,
    },
  },

  // Always leave this as last one,
  // but you can also remove it
  {
    path: "/:catchAll(.*)*",
    component: () => import("pages/ErrorNotFound.vue"),
  },
];

export default routes;

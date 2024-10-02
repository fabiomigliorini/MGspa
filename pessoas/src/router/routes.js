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
        name: "pessoaview",
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
        component: () => import("pages/GrupoEconomico/GrupoEconomicoview"),
      },

      {
        path: "/nota-fiscal/dashboard",
        name: "notafiscaldash",
        component: () => import("pages/NotaFiscal/Index.vue"),
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

const routes = [
  // OFFLINE
  {
    path: "/",
    component: () => import("layouts/OfflineLayout.vue"),
    children: [
      { path: "", component: () => import("pages/IndexPage.vue") },
      {
        path: "/offline/:uuid",
        name: "offline",
        component: () => import("pages/IndexPage.vue"),
      },
      {
        path: "/negocio/:codnegocio",
        name: "negocio",
        component: () => import("pages/IndexPage.vue"),
      },
    ],
  },

  // DEVOLUCAO
  {
    path: "/offline/:uuid/devolucao",
    component: () => import("layouts/DevolucaoLayout.vue"),
    children: [
      { path: "", component: () => import("pages/DevolucaoPage.vue") },
    ],
  },

  // COMANDAS VENDEDOR
  {
    path: "/comanda-vendedor",
    component: () => import("layouts/ComandaLayout.vue"),
    children: [{ path: "", component: () => import("pages/ComandaPage.vue") }],
  },

  // ORCAMENTOS
  {
    path: "/offline/:uuid/orcamento",
    // component: () => import("layouts/OrcamentoLayout.vue"),
    children: [
      { path: "", component: () => import("pages/OrcamentoPage.vue") },
    ],
  },
  {
    path: "/offline/:uuid/orcamento-termica",
    // component: () => import("layouts/OrcamentoLayout.vue"),
    children: [
      { path: "", component: () => import("pages/OrcamentoTermicaPage.vue") },
    ],
  },

  // CONFIG
  {
    path: "/config",
    component: () => import("layouts/ConfigLayout.vue"),
    children: [
      { path: "padrao/", component: () => import("pages/PadraoPage.vue") },
      { path: "pdv/", component: () => import("pages/PdvPage.vue") },
      { path: "pagar-me/", component: () => import("pages/PagarMePage.vue") },
      { path: "saurus-s2-pay/", component: () => import("pages/SaurusS2PayPage.vue") },
      {
        path: "prancheta/",
        component: () => import("pages/PranchetaPage.vue"),
      },
    ],
  },

  // LISTAGEM NEGOCIOS
  {
    path: "/listagem",
    component: () => import("layouts/ListagemLayout.vue"),
    children: [{ path: "", component: () => import("pages/ListagemPage.vue") }],
  },

  // CONFERENCIA CAIXA
  {
    path: "/conferencia",
    component: () => import("layouts/ConferenciaLayout.vue"),
    children: [
      { path: "", component: () => import("pages/ConferenciaPage.vue") },
    ],
  },

  // CONFISSOES
  {
    path: "/confissao",
    component: () => import("layouts/ConfissaoLayout.vue"),
    children: [
      { path: "", component: () => import("pages/ConfissaoPage.vue") },
      {
        path: "faltando",
        component: () => import("pages/ConfissaoFaltandoPage.vue"),
      },
    ],
  },

  {
    path: "/liquidacao",
    component: () => import("layouts/LiquidacaoListagemLayout.vue"),
    children: [
      { path: "", component: () => import("pages/LiquidacaoListagemPage.vue") },
    ],
  },

  // Always leave this as last one,
  // but you can also remove it
  {
    path: "/:catchAll(.*)*",
    name: "ErrorNotFound",
    component: () => import("pages/ErrorNotFound.vue"),
  },
];

export default routes;

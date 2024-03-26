const routes = [
  {
    path: "/",
    component: () => import("layouts/MainLayout.vue"),
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
  {
    path: "/config",
    component: () => import("layouts/ConfigLayout.vue"),
    children: [
      { path: "padrao/", component: () => import("pages/PadraoPage.vue") },
      { path: "pdv/", component: () => import("pages/PdvPage.vue") },
    ],
  },
  {
    path: "/listagem",
    component: () => import("layouts/ListagemLayout.vue"),
    children: [{ path: "", component: () => import("pages/ListagemPage.vue") }],
  },

  {
    path: "/conferencia",
    component: () => import("layouts/ConferenciaLayout.vue"),
    children: [
      { path: "", component: () => import("pages/ConferenciaPage.vue") },
    ],
  },

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

  {
    path: "/devolucao/:codnegocio",
    component: () => import("layouts/DevolucaoLayout.vue"),
    children: [
      { path: "", component: () => import("pages/DevolucaoPage.vue") },
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

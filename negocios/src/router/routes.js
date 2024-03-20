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

  // Always leave this as last one,
  // but you can also remove it
  {
    path: "/:catchAll(.*)*",
    name: "ErrorNotFound",
    component: () => import("pages/ErrorNotFound.vue"),
  },
];

export default routes;

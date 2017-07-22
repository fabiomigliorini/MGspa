import Vue from 'vue'
import VueRouter from 'vue-router'

Vue.use(VueRouter)

function load (component) {
  return () => System.import(`components/${component}.vue`)
}

export default new VueRouter({
  /*
   * NOTE! VueRouter "history" mode DOESN'T works for Cordova builds,
   * it is only to be used only for websites.
   *
   * If you decide to go with "history" mode, please also open /config/index.js
   * and set "build.publicPath" to something other than an empty string.
   * Example: '/' instead of current ''
   *
   * If switching back to default "hash" mode, don't forget to set the
   * build publicPath back to '' so Cordova builds work again.
   */

  routes: [

    // Básicos
    { path: '/', component: load('Index') }, // Default
    { path: '/login', component: load('Login') }, // Login

     // Marca
    { path: '/marca', component: load('views/marca/Index') },
    { path: '/marca/:id', component: load('views/marca/View') }, // Marca

    // Grupo de Usuarios
    { path: '/grupo-usuario', component: load('views/grupo-usuario/Index') },
    { path: '/grupo-usuario/create', component: load('views/grupo-usuario/Create') },
    { path: '/grupo-usuario/:id', component: load('views/grupo-usuario/View') },
    { path: '/grupo-usuario/:id/update', component: load('views/grupo-usuario/Update') },

    // Permissao
    { path: '/permissao', component: load('views/permissao/Index') },

    // Erro
    { path: '*', component: load('Error404') } // Not found
  ]
})

import { defineRouter } from '#q-app/wrappers'
import { createRouter, createMemoryHistory, createWebHistory, createWebHashHistory } from 'vue-router'
import routes from './routes'
import { authStore } from 'stores/auth'
import { api } from 'src/boot/axios'

/*
 * If not building with SSR mode, you can
 * directly export the Router instantiation;
 *
 * The function below can be async too; either use
 * async/await or return a Promise which resolves
 * with the Router instance.
 */

export default defineRouter(function (/* { store, ssrContext } */) {
  const createHistory = process.env.SERVER
    ? createMemoryHistory
    : (process.env.VUE_ROUTER_MODE === 'history' ? createWebHistory : createWebHashHistory)

  const Router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes,

    // Leave this as is and make changes in quasar.conf.js instead!
    // quasar.conf.js -> build -> vueRouterMode
    // quasar.conf.js -> build -> publicPath
    history: createHistory(process.env.VUE_ROUTER_BASE)
  })

  Router.beforeEach(async (to, from, next) => {
    if (to.meta?.auth) {
      const auth = authStore()

      if (auth.token) {
        const EstaAutenticado = await auth.verificaToken()

        if (EstaAutenticado) {
          auth.usuarioLogado = EstaAutenticado
          auth.username(EstaAutenticado.usuario)

          // Envia sempre o Token em todas as requisições
          api.defaults.headers.common['Authorization'] = 'Bearer ' + auth.token
          // api.defaults.headers.put['Access-Control-Allow-Origin'] = '*'
          // api.defaults.headers.put['Content-Type'] ='application/json;charset=utf-8'
          // api.defaults.withCredentials = true
          next()
        } else {
          api.defaults.headers.common['Authorization'] = ''
          next({ name: 'login' })
        }
      } else {
        api.defaults.headers.common['Authorization'] = ''
        next({ name: 'login' })
      }
    } else {
      next()
    }

  })

  return Router
})

import { route } from 'quasar/wrappers'
import {
  createRouter,
  createMemoryHistory,
  createWebHistory,
  createWebHashHistory,
} from 'vue-router'
import routes from './routes'
import { useAuthStore } from 'stores/index'

export default route(function (/* { store, ssrContext } */) {
  const createHistory = process.env.SERVER
    ? createMemoryHistory
    : process.env.VUE_ROUTER_MODE === 'history'
      ? createWebHistory
      : createWebHashHistory

  const Router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes,
    history: createHistory(process.env.MODE === 'ssr' ? void 0 : process.env.VUE_ROUTER_BASE),
  })

  Router.beforeEach(async (to, from, next) => {
    if (!to.meta?.auth) return next()

    const auth = useAuthStore()
    if (!auth.token) {
      return next({ name: 'login', query: { redirect: to.fullPath } })
    }

    const valido = await auth.validarToken()
    if (!valido) {
      return next({ name: 'login', query: { redirect: to.fullPath } })
    }

    next()
  })
  return Router
})

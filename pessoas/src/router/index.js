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

  Router.beforeEach(async (to, _from, next) => {
    const auth = useAuthStore()

    // Proteção contra loop infinito de redirect pro SSO
    const loopCheck = sessionStorage.getItem('login_redirect_check')
    if (loopCheck && parseInt(loopCheck) > 3) {
      console.error('Loop infinito detectado!')
      sessionStorage.removeItem('login_redirect_check')
      return next(false)
    }

    if (to.path === '/login') {
      sessionStorage.removeItem('login_redirect_check')
      return next()
    }

    if (to.meta?.auth) {
      if (!auth.token) {
        auth.gravarUrlRetorno(to.fullPath)
        const count = parseInt(loopCheck || 0) + 1
        sessionStorage.setItem('login_redirect_check', count)
        const currentUrl = encodeURIComponent(window.location.origin + '/login')
        window.location.href = `${process.env.API_AUTH_URL}/login?redirect_uri=${currentUrl}`
        return next(false)
      }

      sessionStorage.removeItem('login_redirect_check')

      if (!auth.usuario) {
        const valido = await auth.validarToken()
        if (!valido) {
          auth.gravarUrlRetorno(to.fullPath)
          const currentUrl = encodeURIComponent(window.location.origin + '/login')
          window.location.href = `${process.env.API_AUTH_URL}/login?redirect_uri=${currentUrl}`
          return next(false)
        }
      }
    }

    next()
  })
  return Router
})

import { route } from 'quasar/wrappers'
import {
  createRouter,
  createMemoryHistory,
  createWebHistory,
  createWebHashHistory,
} from 'vue-router'
import { Notify } from 'quasar'
import routes from './routes'
import { useAuthStore } from 'src/stores/auth'

export default route(function (/* { store, ssrContext } */) {
  const createHistory = process.env.SERVER
    ? createMemoryHistory
    : process.env.VUE_ROUTER_MODE === 'history'
      ? createWebHistory
      : createWebHashHistory

  const Router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes,
    history: createHistory(process.env.VUE_ROUTER_BASE),
  })

  Router.beforeEach(async (to, _from, next) => {
    const authStore = useAuthStore()

    // Proteção contra loop infinito de redirect pro SSO
    const loopCheck = sessionStorage.getItem('login_redirect_check')
    if (loopCheck && parseInt(loopCheck) > 3) {
      console.error('Loop infinito detectado!')
      sessionStorage.removeItem('login_redirect_check')
      Notify.create({
        type: 'negative',
        message: 'Loop infinito detectado no login. Entre em contato com o suporte.',
        timeout: 0,
        actions: [{ icon: 'close', color: 'white' }],
      })
      return next(false)
    }

    if (to.path === '/login' || to.path === '/sem-permissao') {
      sessionStorage.removeItem('login_redirect_check')
      return next()
    }

    if (to.meta?.auth) {
      if (!authStore.token) {
        authStore.setRedirectUrl(to.fullPath)
        const count = parseInt(loopCheck || 0) + 1
        sessionStorage.setItem('login_redirect_check', count)
        const currentUrl = encodeURIComponent(window.location.origin + '/login')
        window.location.href = `${process.env.API_AUTH_URL}/login?redirect_uri=${currentUrl}`
        return next(false)
      }

      sessionStorage.removeItem('login_redirect_check')

      if (!authStore.user) {
        const isValid = await authStore.validateToken()
        if (!isValid) {
          authStore.setRedirectUrl(to.fullPath)
          const currentUrl = encodeURIComponent(window.location.origin + '/login')
          window.location.href = `${process.env.API_AUTH_URL}/login?redirect_uri=${currentUrl}`
          return next(false)
        }
      }

      if (to.meta?.permissions && to.meta.permissions.length > 0) {
        const hasPermission = authStore.hasAnyPermission(to.meta.permissions)
        if (!hasPermission) {
          console.warn('Usuário sem permissão para:', to.path)
          return next({ name: 'sem-permissao' })
        }
      }
    }

    next()
  })

  Router.afterEach((to) => {
    const titulo = to.meta?.title
    document.title = titulo ? `MG Contas — ${titulo}` : 'MG Contas'
  })

  return Router
})

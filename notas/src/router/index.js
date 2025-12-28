import { route } from 'quasar/wrappers'
import { createRouter, createMemoryHistory, createWebHistory, createWebHashHistory } from 'vue-router'
import routes from './routes'
import { useAuthStore } from 'src/stores/auth'

export default route(function (/* { store, ssrContext } */) {
  const createHistory = process.env.SERVER
    ? createMemoryHistory
    : (process.env.VUE_ROUTER_MODE === 'history' ? createWebHistory : createWebHashHistory)

  const Router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes,
    history: createHistory(process.env.VUE_ROUTER_BASE)
  })

  // Guard global - verifica autenticação antes de cada rota
  Router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore()

    // Proteção contra loop infinito
    const loopCheck = sessionStorage.getItem('login_redirect_check')
    if (loopCheck && parseInt(loopCheck) > 3) {
      console.error('Loop infinito detectado! Limpando e parando...')
      sessionStorage.removeItem('login_redirect_check')
      alert('Erro: Loop infinito detectado. Entre em contato com o suporte.')
      return next(false)
    }

    // Se estiver indo para /login, deixa passar SEM verificações
    if (to.path === '/login') {
      sessionStorage.removeItem('login_redirect_check')
      return next()
    }

    // Se a rota requer autenticação
    if (to.meta?.auth) {
      // Verifica se tem token
      if (!authStore.token) {
        console.log('Sem token, redirecionando para login...')

        // Incrementa contador de redirects
        const count = parseInt(loopCheck || 0) + 1
        sessionStorage.setItem('login_redirect_check', count)

        const currentUrl = encodeURIComponent(window.location.origin + '/#/login')
        window.location.href = `${process.env.API_AUTH_URL}/login?redirect_uri=${currentUrl}`
        return next(false)
      }

      // Se chegou aqui, tem token - limpa o contador
      sessionStorage.removeItem('login_redirect_check')

      // Valida o token (se ainda não tiver dados do usuário)
      if (!authStore.user) {
        const isValid = await authStore.validateToken()

        if (!isValid) {
          console.log('Token inválido, redirecionando para login...')
          const currentUrl = encodeURIComponent(window.location.origin + '/#/login')
          window.location.href = `${process.env.API_AUTH_URL}/login?redirect_uri=${currentUrl}`
          return next(false)
        }
      }

      // Verifica permissões específicas (se definidas na rota)
      if (to.meta?.permissions && to.meta.permissions.length > 0) {
        const hasPermission = authStore.hasAnyPermission(to.meta.permissions)

        if (!hasPermission) {
          console.warn('Usuário sem permissão para:', to.path)
          return next({ name: 'home' })
        }
      }
    }

    next()
  })

  return Router
})
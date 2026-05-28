import Vue from 'vue'
import VueRouter from 'vue-router'

import routes from './routes'
import auth from '../services/auth'

Vue.use(VueRouter)

const Router = new VueRouter({
  /*
   * NOTE! Change Vue Router mode from quasar.conf.js -> build -> vueRouterMode
   *
   * If you decide to go with "history" mode, please also set "build.publicPath"
   * to something other than an empty string.
   * Example: '/' instead of ''
   */

  // Leave as is and change from quasar.conf.js instead!
  mode: process.env.VUE_ROUTER_MODE,
  base: process.env.VUE_ROUTER_BASE,
  scrollBehavior: () => ({ y: 0 }),
  routes
})

// Guard de autenticação — mesmo padrão dos apps Vue 3 (contas/notas/pessoas).
// Toda rota é protegida exceto /login. Sem token → grava urlRetorno e
// redireciona pro SSO em ${API_AUTH_URL}/login. Com token mas sem usuário
// carregado → valida via /userinfo (auth.validarToken); se falhar, repete o
// fluxo do SSO. Protege contra loop infinito via sessionStorage counter.
Router.beforeEach(async (to, from, next) => {
  if (to.path === '/login') {
    sessionStorage.removeItem('login_redirect_check')
    return next()
  }

  const loopCheck = parseInt(sessionStorage.getItem('login_redirect_check') || '0', 10)
  if (loopCheck > 3) {
    console.error('Loop infinito de login detectado!')
    sessionStorage.removeItem('login_redirect_check')
    return next(false)
  }

  if (!auth.state.token) {
    sessionStorage.setItem('login_redirect_check', String(loopCheck + 1))
    auth.redirecionarParaLogin(to.fullPath)
    return next(false)
  }

  sessionStorage.removeItem('login_redirect_check')

  if (!auth.state.usuario) {
    const ok = await auth.validarToken()
    if (!ok) {
      auth.redirecionarParaLogin(to.fullPath)
      return next(false)
    }
  }

  next()
})

export default Router

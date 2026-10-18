import { defineBoot } from '#q-app/wrappers'
import axios from 'axios'
import { useAuthStore } from 'stores/auth'
import { api } from 'src/services/api'

// A instância nasce em src/services/api.js; aqui só os interceptors e o $api.
// Ninguém importa este arquivo: quem precisa do axios importa de src/services/api.
export default defineBoot(({ app }) => {
  api.interceptors.request.use(
    (config) => {
      const sAuth = useAuthStore()
      let tokenCookie = document.cookie.split(';').find((c) => c.trim().startsWith('access_token='))
      if (tokenCookie) {
        sAuth.token.access_token = tokenCookie.split('=')[1]
      }

      if (sAuth.token.access_token) {
        config.headers['Authorization'] = 'Bearer ' + sAuth.token.access_token
      }
      return config
    },
    (error) => {
      return Promise.reject(error)
    },
  )

  api.interceptors.response.use(
    (response) => {
      // Retorna a resposta sem alteração se tudo estiver OK
      return response
    },
    (error) => {
      // Verifica se a resposta do erro existe e se o status é 401 (nao autenticado)
      if (error.response && error.response.status === 401) {
        // Limpa o cookie de autenticação
        document.cookie = 'access_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;'

        // Limpa o token e o usuario no store
        const sAuth = useAuthStore()
        sAuth.usuario = {}
        sAuth.token = {}

        // abre o dialog de login
        sAuth.dialog.login = true

        return Promise.reject(error)
      }
      // Para qualquer outro erro, o erro é propagado
      return Promise.reject(error)
    },
  )

  app.config.globalProperties.$axios = axios
  app.config.globalProperties.$api = api
})

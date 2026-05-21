import { boot } from 'quasar/wrappers'
import axios from 'axios'
import { useAuthStore } from 'stores/auth'
const sAuth = useAuthStore()

// API_URL inclui o sufixo /api/ ; o baseURL da instancia e so o host
const api = axios.create({ baseURL: process.env.API_URL.replace(/\/api\/?$/, '') })

api.interceptors.request.use(
  (config) => {
    // Autorizacao
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

export default boot(({ app }) => {
  app.config.globalProperties.$axios = axios
  app.config.globalProperties.$api = api
})

export { api }

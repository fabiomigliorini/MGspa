import axios from 'axios'
import { Notify } from 'quasar'
import { api } from '../services/api'
import auth from '../services/auth'

// Padrão OAuth/OIDC alinhado com os apps Vue 3 (contas/notas/pessoas):
//   - Bearer token vem do auth.state.token (= localStorage 'access_token')
//   - 401 → limpa token + redireciona pro SSO (${API_AUTH_URL}/login)
//
// Compat: $axios continua sendo a instância com baseURL (todo código antigo
// usa vm.$axios.get('alguma/rota') esperando prefixo de API_URL).

export default ({ Vue }) => {
  // Request: injeta Bearer se houver token
  api.interceptors.request.use(
    config => {
      if (auth.state.token) {
        config.headers.Authorization = `Bearer ${auth.state.token}`
      }
      return config
    },
    error => Promise.reject(error)
  )

  // Response: trata 401 (token expirado/inválido)
  api.interceptors.response.use(
    response => response,
    error => {
      if (error.response && error.response.status === 401) {
        console.warn('Token expirado ou inválido (401)')
        auth.gravarToken(null)
        auth.state.usuario = null

        Notify.create({
          type: 'negative',
          message: 'Sessão expirada. Faça login novamente.'
        })

        setTimeout(() => {
          auth.redirecionarParaLogin(window.location.hash.slice(1) || '/')
        }, 1500)
      }
      return Promise.reject(error)
    }
  )

  // $axios = instância com baseURL/interceptors (mantém compat com código antigo)
  // $api = idem (alias por consistência com os apps Vue 3)
  // $auth = service singleton de autenticação
  Vue.prototype.$axios = api
  Vue.prototype.$api = api
  Vue.prototype.$auth = auth
}

export { axios, api }

import { boot } from 'quasar/wrappers'
import axios from 'axios'
import { useAuthStore } from 'stores/auth'
const sAuth = useAuthStore()

// timeout: sem ele, um socket HTTP/2 morto reaproveitado pendura a request por minutos.
const api = axios.create({ baseURL: process.env.API_URL, timeout: 15000 })

// Dedup global de requisicoes mutantes identicas em voo: protege contra
// double-submit (clicar varias vezes em Salvar antes da resposta voltar).
// O 2o POST/PUT/PATCH/DELETE identico reusa a promessa do 1o, entao apenas
// uma request chega no backend.
const requisicoesEmVoo = new Map()

const chaveRequisicao = (config) => {
  const metodo = (config.method || 'get').toLowerCase()
  if (!['post', 'put', 'patch', 'delete'].includes(metodo)) return null
  const corpo = typeof config.data === 'string' ? config.data : JSON.stringify(config.data ?? '')
  return `${metodo}:${config.baseURL || ''}${config.url || ''}:${corpo}`
}

const adapterPadrao = axios.getAdapter(api.defaults.adapter)

api.defaults.adapter = (config) => {
  const chave = chaveRequisicao(config)
  if (!chave) return adapterPadrao(config)
  if (requisicoesEmVoo.has(chave)) return requisicoesEmVoo.get(chave)
  const promessa = adapterPadrao(config).finally(() => requisicoesEmVoo.delete(chave))
  requisicoesEmVoo.set(chave, promessa)
  return promessa
}

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

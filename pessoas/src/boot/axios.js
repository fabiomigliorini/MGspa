import { boot } from 'quasar/wrappers'
import axios from 'axios'
import { Notify } from 'quasar'
import { useAuthStore } from 'src/stores/auth'

const api = axios.create({
  baseURL: process.env.API_URL,
  headers: { 'Content-type': 'application/json' },
  // Sem timeout, uma conexão HTTP/2 meia-aberta reaproveitada do pool do
  // navegador deixa a request pendurada por minutos. 15s aborta o socket morto.
  timeout: 15000,
})

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

export default boot(({ app }) => {
  api.interceptors.request.use(
    (config) => {
      const authStore = useAuthStore()
      const cookie = document.cookie.split(';').find((c) => c.trim().startsWith('access_token='))
      const tokenAtivo = (cookie ? cookie.split('=')[1] : null) || authStore.token

      if (tokenAtivo) {
        config.headers.Authorization = `Bearer ${tokenAtivo}`
      }
      return config
    },
    (error) => Promise.reject(error),
  )

  api.interceptors.response.use(
    (response) => response,
    (error) => {
      if (error.response?.status === 401) {
        const authStore = useAuthStore()
        Notify.create({
          type: 'negative',
          message: 'Sessão expirada. Faça login novamente.',
        })
        setTimeout(() => authStore.logout(), 1500)
      }
      return Promise.reject(error)
    },
  )

  app.config.globalProperties.$axios = axios
  app.config.globalProperties.$api = api
})

export { api }

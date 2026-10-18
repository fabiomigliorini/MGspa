import axios from 'axios'

// Instância única do axios. Fica aqui, e não no boot, porque o Quasar carrega
// o boot com import() dinâmico: importar o boot de stores e componentes faz o
// build avisar INEFFECTIVE_DYNAMIC_IMPORT e puxa o boot antes do Pinia existir.
// Sem Vue, Quasar nem store aqui: os interceptors ficam no boot/axios.js.
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

export default api
export { api }

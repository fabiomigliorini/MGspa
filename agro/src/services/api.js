import axios from 'axios'

// baseURL = process.env.API_URL (= .../api/), padrão do monorepo. Os endpoints
// de domínio ficam sob v1/ — cada chamada inclui o prefixo (ex.: 'v1/cultura'),
// como nos outros apps, pra reaproveitar os componentes de @components.
const api = axios.create({
  baseURL: process.env.API_URL,
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

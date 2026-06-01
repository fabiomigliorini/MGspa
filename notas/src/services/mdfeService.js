import api from './api'

// MDFe — Manifesto Eletrônico de Documentos Fiscais.
// index/show/update retornam API Resource embrulhado em { data: ... }.
// As ações de workflow (criar-xml, enviar, consultar, cancelar, encerrar)
// retornam o JSON cru do nfephp (cStat/xMotivo).
export default {
  async list(params = {}) {
    const response = await api.get('/v1/mdfe', { params })
    return response.data // { data: [...], links, meta }
  },
  async get(codmdfe) {
    const response = await api.get(`/v1/mdfe/${codmdfe}`)
    return response.data.data
  },
  async update(codmdfe, payload) {
    const response = await api.put(`/v1/mdfe/${codmdfe}`, payload)
    return response.data.data
  },
  async criarDaNfeChave(nfechave) {
    const chave = String(nfechave).replace(/\D/g, '')
    const response = await api.post(`/v1/mdfe/criar-da-nfechave/${chave}`)
    return response.data.data
  },

  // ----- Workflow SEFAZ -----
  async criarXml(codmdfe) {
    const response = await api.post(`/v1/mdfe/${codmdfe}/criar-xml`)
    return response.data
  },
  async enviar(codmdfe) {
    const response = await api.post(`/v1/mdfe/${codmdfe}/enviar`)
    return response.data
  },
  async consultarEnvio(codmdfe, codmdfeenviosefaz = null) {
    const url = codmdfeenviosefaz
      ? `/v1/mdfe/${codmdfe}/consultar-envio/${codmdfeenviosefaz}`
      : `/v1/mdfe/${codmdfe}/consultar-envio`
    const response = await api.post(url)
    return response.data
  },
  async consultar(codmdfe) {
    const response = await api.post(`/v1/mdfe/${codmdfe}/consultar`)
    return response.data
  },
  async cancelar(codmdfe, justificativa) {
    const response = await api.post(`/v1/mdfe/${codmdfe}/cancelar`, { justificativa })
    return response.data
  },
  async encerrar(codmdfe) {
    const response = await api.post(`/v1/mdfe/${codmdfe}/encerrar`)
    return response.data
  },
  async damdfe(codmdfe) {
    const response = await api.get(`/v1/mdfe/${codmdfe}/damdfe`, { responseType: 'blob' })
    const blob = new Blob([response.data], { type: 'application/pdf' })
    return URL.createObjectURL(blob)
  },
}

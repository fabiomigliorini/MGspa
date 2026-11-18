import api from './api'

// Nota fiscal de servico (NFS-e Nacional, TASK-279).
// As acoes no Sistema Nacional (enviar, consultar, cancelar, e-mail, DANFSe, XML) ficam no
// @components/MgNotaFiscalServicoAcoes.vue, que usa a instancia de api direto — igual ao
// MgNotaFiscalAcoes da NF-e.
export default {
  async list(params = {}) {
    const response = await api.get('/v1/nota-fiscal-servico', { params })
    return response.data
  },

  async get(codnotafiscalservico) {
    const response = await api.get(`/v1/nota-fiscal-servico/${codnotafiscalservico}`)
    return response.data
  },

  async create(data) {
    const response = await api.post('/v1/nota-fiscal-servico', data)
    return response.data
  },

  async update(codnotafiscalservico, data) {
    const response = await api.put(`/v1/nota-fiscal-servico/${codnotafiscalservico}`, data)
    return response.data
  },

  async delete(codnotafiscalservico) {
    await api.delete(`/v1/nota-fiscal-servico/${codnotafiscalservico}`)
  },

  async filiais() {
    const response = await api.get('/v1/nota-fiscal-servico/filiais')
    return Array.isArray(response.data) ? response.data : response.data?.data || []
  },
}

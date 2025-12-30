import api from './api'

export default {
  async list(codnotafiscal) {
    const response = await api.get(`/v1/nota-fiscal/${codnotafiscal}/carta-correcao`)
    return response.data
  },

  async create(codnotafiscal, data) {
    const response = await api.post(`/v1/nota-fiscal/${codnotafiscal}/carta-correcao`, data)
    return response.data
  },

  async update(codnotafiscal, codnotafiscalcartacorrecao, data) {
    const response = await api.put(`/v1/nota-fiscal/${codnotafiscal}/carta-correcao/${codnotafiscalcartacorrecao}`, data)
    return response.data
  },

  async delete(codnotafiscal, codnotafiscalcartacorrecao) {
    await api.delete(`/v1/nota-fiscal/${codnotafiscal}/carta-correcao/${codnotafiscalcartacorrecao}`)
  }
}

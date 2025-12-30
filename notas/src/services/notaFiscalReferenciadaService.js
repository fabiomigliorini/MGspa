import api from './api'

export default {
  async list(codnotafiscal) {
    const response = await api.get(`/v1/nota-fiscal/${codnotafiscal}/referenciada`)
    return response.data
  },

  async create(codnotafiscal, data) {
    const response = await api.post(`/v1/nota-fiscal/${codnotafiscal}/referenciada`, data)
    return response.data
  },

  async delete(codnotafiscal, codnotafiscalreferenciada) {
    await api.delete(`/v1/nota-fiscal/${codnotafiscal}/referenciada/${codnotafiscalreferenciada}`)
  }
}

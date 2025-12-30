import api from './api'

export default {
  async list(codnotafiscal) {
    const response = await api.get(`/v1/nota-fiscal/${codnotafiscal}/duplicata`)
    return response.data
  },

  async create(codnotafiscal, data) {
    const response = await api.post(`/v1/nota-fiscal/${codnotafiscal}/duplicata`, data)
    return response.data
  },

  async update(codnotafiscal, codnotafiscalduplicatas, data) {
    const response = await api.put(`/v1/nota-fiscal/${codnotafiscal}/duplicata/${codnotafiscalduplicatas}`, data)
    return response.data
  },

  async delete(codnotafiscal, codnotafiscalduplicatas) {
    await api.delete(`/v1/nota-fiscal/${codnotafiscal}/duplicata/${codnotafiscalduplicatas}`)
  }
}

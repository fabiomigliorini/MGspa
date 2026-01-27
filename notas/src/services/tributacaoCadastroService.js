import api from './api'

export default {
  async list(params = {}) {
    const response = await api.get('/v1/tributacao', { params })
    return response.data
  },

  async get(codtributacao) {
    const response = await api.get(`/v1/tributacao/${codtributacao}`)
    return response.data
  },

  async create(data) {
    const response = await api.post('/v1/tributacao', data)
    return response.data
  },

  async update(codtributacao, data) {
    const response = await api.put(`/v1/tributacao/${codtributacao}`, data)
    return response.data
  },

  async delete(codtributacao) {
    await api.delete(`/v1/tributacao/${codtributacao}`)
  },
}

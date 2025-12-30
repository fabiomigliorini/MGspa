import api from './api'

export default {
  async list(params = {}) {
    const response = await api.get('/v1/nota-fiscal', { params })
    return response.data
  },

  async get(codnotafiscal) {
    const response = await api.get(`/v1/nota-fiscal/${codnotafiscal}`)
    return response.data
  },

  async create(data) {
    const response = await api.post('/v1/nota-fiscal', data)
    return response.data
  },

  async update(codnotafiscal, data) {
    const response = await api.put(`/v1/nota-fiscal/${codnotafiscal}`, data)
    return response.data
  },

  async delete(codnotafiscal) {
    await api.delete(`/v1/nota-fiscal/${codnotafiscal}`)
  }
}

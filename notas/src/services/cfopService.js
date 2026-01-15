import api from './api'

export default {
  async list(params = {}) {
    const response = await api.get('/v1/cfop', { params })
    return response.data
  },

  async get(codcfop) {
    const response = await api.get(`/v1/cfop/${codcfop}`)
    return response.data
  },

  async create(data) {
    const response = await api.post('/v1/cfop', data)
    return response.data
  },

  async update(codcfop, data) {
    const response = await api.put(`/v1/cfop/${codcfop}`, data)
    return response.data
  },

  async delete(codcfop) {
    await api.delete(`/v1/cfop/${codcfop}`)
  },
}

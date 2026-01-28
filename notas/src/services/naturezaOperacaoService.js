import api from './api'

export default {
  async list(params = {}) {
    const response = await api.get('/v1/natureza-operacao', { params })
    return response.data
  },

  async get(codnaturezaoperacao) {
    const response = await api.get(`/v1/natureza-operacao/${codnaturezaoperacao}`)
    return response.data
  },

  async create(data) {
    const response = await api.post('/v1/natureza-operacao', data)
    return response.data
  },

  async update(codnaturezaoperacao, data) {
    const response = await api.put(`/v1/natureza-operacao/${codnaturezaoperacao}`, data)
    return response.data
  },

  async delete(codnaturezaoperacao) {
    await api.delete(`/v1/natureza-operacao/${codnaturezaoperacao}`)
  },
}

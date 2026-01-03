import api from './api'

export default {
  async list(codnotafiscal) {
    const response = await api.get(`/v1/nota-fiscal/${codnotafiscal}/item`)
    return response.data
  },

  async get(codnotafiscal, codnotafiscalprodutobarra) {
    const response = await api.get(
      `/v1/nota-fiscal/${codnotafiscal}/item/${codnotafiscalprodutobarra}`
    )
    return response.data
  },

  async create(codnotafiscal, data) {
    const response = await api.post(`/v1/nota-fiscal/${codnotafiscal}/item`, data)
    return response.data
  },

  async update(codnotafiscal, codnotafiscalprodutobarra, data) {
    const response = await api.put(
      `/v1/nota-fiscal/${codnotafiscal}/item/${codnotafiscalprodutobarra}`,
      data
    )
    return response.data
  },

  async delete(codnotafiscal, codnotafiscalprodutobarra) {
    const response = await api.delete(
      `/v1/nota-fiscal/${codnotafiscal}/item/${codnotafiscalprodutobarra}`
    )
    return response.data
  },
}

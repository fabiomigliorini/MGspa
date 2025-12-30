import api from './api'

export default {
  async list(codnotafiscal) {
    const response = await api.get(`/v1/nota-fiscal/${codnotafiscal}/pagamento`)
    return response.data
  },

  async get(codnotafiscal, codnotafiscalpagamento) {
    const response = await api.get(`/v1/nota-fiscal/${codnotafiscal}/pagamento/${codnotafiscalpagamento}`)
    return response.data
  },

  async create(codnotafiscal, data) {
    const response = await api.post(`/v1/nota-fiscal/${codnotafiscal}/pagamento`, data)
    return response.data
  },

  async update(codnotafiscal, codnotafiscalpagamento, data) {
    const response = await api.put(`/v1/nota-fiscal/${codnotafiscal}/pagamento/${codnotafiscalpagamento}`, data)
    return response.data
  },

  async delete(codnotafiscal, codnotafiscalpagamento) {
    await api.delete(`/v1/nota-fiscal/${codnotafiscal}/pagamento/${codnotafiscalpagamento}`)
  }
}

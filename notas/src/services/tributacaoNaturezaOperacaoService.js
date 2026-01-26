import api from './api'

export default {
  async list(codnaturezaoperacao, params = {}) {
    const response = await api.get(`/v1/natureza-operacao/${codnaturezaoperacao}/tributacao`, {
      params,
    })
    return response.data
  },

  async get(codnaturezaoperacao, codtributacaonaturezaoperacao) {
    const response = await api.get(
      `/v1/natureza-operacao/${codnaturezaoperacao}/tributacao/${codtributacaonaturezaoperacao}`,
    )
    return response.data
  },

  async create(codnaturezaoperacao, data) {
    const response = await api.post(`/v1/natureza-operacao/${codnaturezaoperacao}/tributacao`, data)
    return response.data
  },

  async update(codnaturezaoperacao, codtributacaonaturezaoperacao, data) {
    const response = await api.put(
      `/v1/natureza-operacao/${codnaturezaoperacao}/tributacao/${codtributacaonaturezaoperacao}`,
      data,
    )
    return response.data
  },

  async delete(codnaturezaoperacao, codtributacaonaturezaoperacao) {
    await api.delete(
      `/v1/natureza-operacao/${codnaturezaoperacao}/tributacao/${codtributacaonaturezaoperacao}`,
    )
  },
}

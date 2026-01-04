import api from './api'

export default {
  async create(codnotafiscal, texto) {
    const response = await api.post(`/v1/nota-fiscal/${codnotafiscal}/carta-correcao`, { texto })
    return response.data
  },
}

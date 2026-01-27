import api from './api'

export default {
  // Paises
  async listPaises() {
    const response = await api.get('/v1/pais/')
    return response.data
  },

  async createPais(data) {
    const response = await api.post('/v1/pais/', data)
    return response.data
  },

  async deletePais(codpais) {
    await api.delete(`/v1/pais/${codpais}`)
  },

  // Estados
  async listEstados(codpais) {
    const response = await api.get(`/v1/pais/${codpais}/estado`)
    return response.data
  },

  async createEstado(codpais, data) {
    const response = await api.post(`/v1/pais/${codpais}/estado`, data)
    return response.data
  },

  async updateEstado(codpais, codestado, data) {
    const response = await api.put(`/v1/pais/${codpais}/estado/${codestado}`, data)
    return response.data
  },

  async deleteEstado(codpais, codestado) {
    await api.delete(`/v1/pais/${codpais}/estado/${codestado}`)
  },

  async listCidades(codpais, codestado, params = {}) {
    const response = await api.get(`/v1/pais/${codpais}/estado/${codestado}/cidade`, { params })
    return response.data
  },

  async getCidade(codpais, codestado, codcidade) {
    const response = await api.get(`/v1/pais/${codpais}/estado/${codestado}/cidade/${codcidade}`)
    return response.data
  },

  async createCidade(codpais, codestado, data) {
    const response = await api.post(`/v1/pais/${codpais}/estado/${codestado}/cidade`, data)
    return response.data
  },

  async updateCidade(codpais, codestado, codcidade, data) {
    const response = await api.put(`/v1/pais/${codpais}/estado/${codestado}/cidade/${codcidade}`, data)
    return response.data
  },

  async deleteCidade(codpais, codestado, codcidade) {
    await api.delete(`/v1/pais/${codpais}/estado/${codestado}/cidade/${codcidade}`)
  },
}

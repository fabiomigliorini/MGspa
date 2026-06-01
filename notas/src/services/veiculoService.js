import api from './api'

// Veículos, Conjuntos e Tipos de Veículo (usados no MDFe).
// Os endpoints de conjunto vêm embrulhados em { data: ... } (API Resource),
// os de veículo e tipo retornam o objeto/array direto.
export default {
  // ----- Veículo -----
  async listVeiculos() {
    const response = await api.get('/v1/veiculo')
    return response.data
  },
  async getVeiculo(id) {
    const response = await api.get(`/v1/veiculo/${id}`)
    return response.data
  },
  async createVeiculo(payload) {
    const response = await api.post('/v1/veiculo', payload)
    return response.data
  },
  async updateVeiculo(id, payload) {
    const response = await api.put(`/v1/veiculo/${id}`, payload)
    return response.data
  },
  async inativarVeiculo(id) {
    const response = await api.post(`/v1/veiculo/${id}/inativo`)
    return response.data
  },
  async ativarVeiculo(id) {
    const response = await api.delete(`/v1/veiculo/${id}/inativo`)
    return response.data
  },
  async deleteVeiculo(id) {
    await api.delete(`/v1/veiculo/${id}`)
  },

  // ----- Conjunto (Resource → desembrulha .data) -----
  async listConjuntos() {
    const response = await api.get('/v1/veiculo-conjunto')
    return response.data.data
  },
  async getConjunto(id) {
    const response = await api.get(`/v1/veiculo-conjunto/${id}`)
    return response.data.data
  },
  async createConjunto(payload) {
    const response = await api.post('/v1/veiculo-conjunto', payload)
    return response.data.data
  },
  async updateConjunto(id, payload) {
    const response = await api.put(`/v1/veiculo-conjunto/${id}`, payload)
    return response.data.data
  },
  async inativarConjunto(id) {
    const response = await api.post(`/v1/veiculo-conjunto/${id}/inativo`)
    return response.data.data
  },
  async ativarConjunto(id) {
    const response = await api.delete(`/v1/veiculo-conjunto/${id}/inativo`)
    return response.data.data
  },
  async deleteConjunto(id) {
    await api.delete(`/v1/veiculo-conjunto/${id}`)
  },

  // ----- Tipo -----
  async listTipos() {
    const response = await api.get('/v1/veiculo-tipo')
    return response.data
  },
  async getTipo(id) {
    const response = await api.get(`/v1/veiculo-tipo/${id}`)
    return response.data
  },
  async createTipo(payload) {
    const response = await api.post('/v1/veiculo-tipo', payload)
    return response.data
  },
  async updateTipo(id, payload) {
    const response = await api.put(`/v1/veiculo-tipo/${id}`, payload)
    return response.data
  },
  async inativarTipo(id) {
    const response = await api.post(`/v1/veiculo-tipo/${id}/inativo`)
    return response.data
  },
  async ativarTipo(id) {
    const response = await api.delete(`/v1/veiculo-tipo/${id}/inativo`)
    return response.data
  },
  async deleteTipo(id) {
    await api.delete(`/v1/veiculo-tipo/${id}`)
  },

  // ----- Selects -----
  async selectVeiculos() {
    const response = await api.get('/v1/select/veiculo')
    return response.data
  },
  async selectTipos() {
    const response = await api.get('/v1/select/veiculo-tipo')
    return response.data
  },
}

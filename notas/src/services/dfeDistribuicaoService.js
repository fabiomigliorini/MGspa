import api from './api'

export default {
  async list(params = {}) {
    const response = await api.get('/v1/dfe/distribuicao', { params })
    return response.data
  },

  async xml(coddistribuicaodfe) {
    const response = await api.get(`/v1/dfe/distribuicao/${coddistribuicaodfe}/xml`, {
      responseType: 'blob',
    })
    const blob = new Blob([response.data], { type: 'text/xml' })
    return URL.createObjectURL(blob)
  },

  async processar(coddistribuicaodfe) {
    const response = await api.get(`/v1/dfe/distribuicao/${coddistribuicaodfe}/processar`)
    return response.data
  },

  async consultarSefaz(coddistribuicaodfe) {
    const response = await api.get(`/v1/dfe/distribuicao/${coddistribuicaodfe}/consultar-sefaz`)
    return response.data
  },

  async filiaisHabilitadas() {
    const response = await api.get('/v1/dfe/filiais-habilitadas')
    return response.data
  },
}

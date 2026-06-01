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

  // Consulta a SEFAZ por novos documentos de uma filial (a partir do nsu informado).
  // Retorna { ultNSU, maxNSU } para controlar o avanço da paginação.
  async distDfe(codfilial, nsu = null) {
    const url =
      nsu != null ? `/v1/nfe-php/dist-dfe/${codfilial}/${nsu}` : `/v1/nfe-php/dist-dfe/${codfilial}`
    const response = await api.post(url)
    return response.data
  },
}

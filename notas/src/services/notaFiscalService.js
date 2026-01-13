import api from './api'

export default {
  async list(params = {}) {
    const response = await api.get('/v1/nota-fiscal', { params })
    return response.data
  },

  async get(codnotafiscal) {
    const response = await api.get(`/v1/nota-fiscal/${codnotafiscal}`)
    return response.data
  },

  async create(data) {
    const response = await api.post('/v1/nota-fiscal', data)
    return response.data
  },

  async update(codnotafiscal, data) {
    const response = await api.put(`/v1/nota-fiscal/${codnotafiscal}`, data)
    return response.data
  },

  async delete(codnotafiscal) {
    await api.delete(`/v1/nota-fiscal/${codnotafiscal}`)
  },

  async duplicar(codnotafiscal) {
    const response = await api.post(`/v1/nota-fiscal/${codnotafiscal}/duplicar`)
    return response.data
  },

  // ==================== NFE ACTIONS ====================
  async criar(codnotafiscal) {
    const response = await api.post(`/v1/nota-fiscal/${codnotafiscal}/criar`)
    return response.data
  },

  async enviarSincrono(codnotafiscal) {
    const response = await api.post(`/v1/nota-fiscal/${codnotafiscal}/enviar-sincrono`)
    return response.data
  },

  async consultar(codnotafiscal) {
    const response = await api.post(`/v1/nota-fiscal/${codnotafiscal}/consultar`)
    return response.data
  },

  async cancelar(codnotafiscal, justificativa) {
    const response = await api.post(`/v1/nota-fiscal/${codnotafiscal}/cancelar`, {
      justificativa,
    })
    return response.data
  },

  async inutilizar(codnotafiscal, justificativa) {
    const response = await api.post(`/v1/nota-fiscal/${codnotafiscal}/inutilizar`, {
      justificativa,
    })
    return response.data
  },

  async mail(codnotafiscal, destinatario = null) {
    const data = destinatario ? { destinatario } : {}
    const response = await api.post(`/v1/nota-fiscal/${codnotafiscal}/mail`, data)
    return response.data
  },

  async danfe(codnotafiscal) {
    // Faz o download do PDF via API (com autenticação via header)
    const response = await api.get(`/v1/nota-fiscal/${codnotafiscal}/danfe`, {
      responseType: 'blob', // Importante para PDFs
    })

    // Cria um URL temporário do blob
    const blob = new Blob([response.data], { type: 'application/pdf' })
    return URL.createObjectURL(blob)
  },

  async cartaCorrecaoPdf(codnotafiscal) {
    // Faz o download do PDF da carta de correção via API (com autenticação via header)
    const response = await api.get(`/v1/nota-fiscal/${codnotafiscal}/carta-correcao/pdf`, {
      responseType: 'blob', // Importante para PDFs
    })

    // Cria um URL temporário do blob
    const blob = new Blob([response.data], { type: 'application/pdf' })
    return URL.createObjectURL(blob)
  },

  async espelho(codnotafiscal) {
    // Faz o download do PDF do espelho via API (com autenticação via header)
    const response = await api.get(`/v1/nota-fiscal/${codnotafiscal}/espelho`, {
      responseType: 'blob', // Importante para PDFs
    })

    // Cria um URL temporário do blob
    const blob = new Blob([response.data], { type: 'application/pdf' })
    return URL.createObjectURL(blob)
  },

  async xml(codnotafiscal) {
    // Faz o download do XML via API (com autenticação via header)
    const response = await api.get(`/v1/nota-fiscal/${codnotafiscal}/xml`, {
      responseType: 'blob',
    })

    // Cria um URL temporário do blob
    const blob = new Blob([response.data], { type: 'application/xml' })
    return URL.createObjectURL(blob)
  },

  async imprimir(codnotafiscal, impressora = null) {
    const data = impressora ? { impressora } : {}
    const response = await api.post(`/v1/nota-fiscal/${codnotafiscal}/imprimir`, data)
    return response.data
  },

  async alterarStatus(codnotafiscal, data) {
    const response = await api.put(`/v1/nota-fiscal/${codnotafiscal}/status`, data)
    return response.data
  },

  async incorporarValores(codnotafiscal) {
    const response = await api.post(`/v1/nota-fiscal/${codnotafiscal}/incorporar-valores`)
    return response.data
  },

  async recalcularTributacao(codnotafiscal) {
    const response = await api.post(`/v1/nota-fiscal/${codnotafiscal}/recalcular-tributacao`)
    return response.data
  },

  async devolucao(codnotafiscal, data) {
    const response = await api.post(`/v1/nota-fiscal/${codnotafiscal}/devolucao`, data)
    return response.data
  },
}

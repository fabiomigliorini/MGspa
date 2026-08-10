import api from './api'

export default {
  async create(codnotafiscal, texto) {
    // Chamada a SEFAZ com retry leva ate ~122s; o timeout global do axios e 15s
    // (existe por causa de socket HTTP/2 morto), entao sobrescreve por request.
    const response = await api.post(
      `/v1/nota-fiscal/${codnotafiscal}/carta-correcao`,
      { texto },
      { timeout: 150000 },
    )
    return response.data
  },
}

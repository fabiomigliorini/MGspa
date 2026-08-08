import api from './api'

// Tabela do IBPT (De Olho no Imposto), base dos tributos aproximados da Lei 12.741.
// A importacao e um arquivo por UF: o endpoint varia por UF de proposito, porque o
// dedup global do api.js usa o corpo da requisicao como chave e, com FormData, dois
// POSTs para a mesma URL colidiriam.
export default {
  async status() {
    const response = await api.get('/v1/ibpt')
    return response.data.data
  },

  async importar(uf, arquivo) {
    const formData = new FormData()
    formData.append('arquivo', arquivo)
    const response = await api.post(`/v1/ibpt/${uf}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      // O timeout global de 15s e curto para um CSV de 2 MB por UF
      timeout: 120000,
    })
    return response.data
  },
}

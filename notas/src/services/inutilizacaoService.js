import api from './api'

/**
 * Inutilizacao de numeracao por FAIXA.
 *
 * A inutilizacao sempre foi um ato sobre um intervalo (o sefazInutiliza recebe nNFIni e
 * nNFFin), mas o sistema so sabia fazer um numero por vez — e criava uma nota fiscal falsa
 * so para carregar o numero. Agora a faixa e de primeira classe.
 */
export default {
  async listar(params = {}) {
    const { data } = await api.get('/v1/inutilizacao', { params })
    return data.data
  },

  async inutilizar(payload) {
    // Chamada a SEFAZ com retry leva ate ~122s; o timeout global do axios e 15s.
    const { data } = await api.post('/v1/inutilizacao', payload, { timeout: 150000 })
    return data
  },

  // Blob via axios: a rota e autenticada, entao window.open direto daria 401/404.
  async abrirXml(codinutilizacao) {
    const { data } = await api.get(`/v1/inutilizacao/${codinutilizacao}/xml`, {
      responseType: 'blob',
    })
    return URL.createObjectURL(new Blob([data], { type: 'application/xml' }))
  },
}

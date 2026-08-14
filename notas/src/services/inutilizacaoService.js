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

  // Filiais que tem inutilizacao NAQUELE ANO, com os totais do ano — as abas do topo.
  async filiais(ano) {
    const { data } = await api.get('/v1/inutilizacao/filial', { params: { ano } })
    return data.data
  },

  // Anos que tem inutilizacao, de todas as filiais — o filtro principal da tela.
  async anos() {
    const { data } = await api.get('/v1/inutilizacao/ano')
    return data.data
  },

  async inutilizar(payload) {
    // Chamada a SEFAZ com retry leva ate ~122s; o timeout global do axios e 15s.
    const { data } = await api.post('/v1/inutilizacao', payload, { timeout: 150000 })
    // O store devolve um Resource, entao o registro vem dentro de data.
    return data.data
  },

  // Nao ha helper de XML aqui: quem for exibir a inutilizacao usa o abrirXml compartilhado
  // (@components/abrirXml) apontando para /v1/inutilizacao/{cod}/xml.
}

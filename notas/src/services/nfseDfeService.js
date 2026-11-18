import api from './api'

// Caixa de DF-e da NFS-e: o que o ADN (Sistema Nacional) distribuiu para a filial (TASK-279)
export default {
  async list(params = {}) {
    const response = await api.get('/v1/nfse-dfe/distribuicao', { params })
    return response.data
  },

  async processar(coddistribuicaonfse) {
    const response = await api.post(`/v1/nfse-dfe/distribuicao/${coddistribuicaonfse}/processar`)
    return response.data
  },

  async filiaisHabilitadas() {
    const response = await api.get('/v1/nfse-dfe/filiais-habilitadas')
    return response.data
  },

  // Busca de novo, no ADN, o documento desta linha (pelo NSU dela)
  async consultarNsu(coddistribuicaonfse) {
    const response = await api.post(
      `/v1/nfse-dfe/distribuicao/${coddistribuicaonfse}/consultar-adn`,
    )
    return response.data
  },

  // Eventos de uma NFS-e no ADN, pela chave (grava os que faltarem)
  async consultarEventos(codfilial, chave) {
    const response = await api.post(`/v1/nfse-dfe/consultar-eventos/${codfilial}/${chave}`)
    return response.data
  },

  // Le UM lote do ADN a partir do nsu (padrao: o ultimo gravado).
  // Retorna { ultNSU, documentos, fim, mensagem }: a tela repete ate fim = true.
  async consultar(codfilial, nsu = null) {
    const url =
      nsu != null
        ? `/v1/nfse-dfe/consultar/${codfilial}/${nsu}`
        : `/v1/nfse-dfe/consultar/${codfilial}`
    const response = await api.post(url)
    return response.data
  },
}

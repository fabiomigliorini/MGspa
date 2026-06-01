import api from './api'

// Exportação para o sistema contábil Domínio (fechamento mensal).
// Os endpoints de geração de arquivo retornam { arquivo, registros[, ...] }.
export default {
  async empresas() {
    const response = await api.get('/v1/dominio/empresas')
    return response.data
  },

  // ----- Geração de arquivos (mes = 'YYYY-MM-DD') -----
  async nfeEntrada(codfilial, mes) {
    const response = await api.post('/v1/dominio/nfe-entrada', { codfilial, mes })
    return response.data
  },
  async nfeSaida(codfilial, modelo, mes) {
    const response = await api.post('/v1/dominio/nfe-saida', { codfilial, modelo, mes })
    return response.data
  },
  async pessoa(codfilial, mes) {
    const response = await api.post('/v1/dominio/pessoa', { codfilial, mes })
    return response.data
  },
  async produto(codfilial, mes) {
    const response = await api.post('/v1/dominio/produto', { codfilial, mes })
    return response.data
  },
  async entrada(codfilial, mes) {
    const response = await api.post('/v1/dominio/entrada', { codfilial, mes })
    return response.data
  },
  async estoque(codfilial, mes) {
    const response = await api.post('/v1/dominio/estoque', { codfilial, mes })
    return response.data
  },

  // ----- Acumuladores (CFOP + CST) -----
  async salvarAcumulador(payload) {
    const response = await api.post('/v1/dominio/acumulador', payload)
    return response.data.data
  },
  async excluirAcumulador(coddominioacumulador) {
    await api.delete(`/v1/dominio/acumulador/${coddominioacumulador}`)
  },
}

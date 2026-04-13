import api from './api'

export default {
  async list(params = {}) {
    const response = await api.get('/v1/nfe-terceiro', { params })
    return response.data
  },

  async get(codnfeterceiro) {
    const response = await api.get(`/v1/nfe-terceiro/${codnfeterceiro}`)
    return response.data
  },

  async update(codnfeterceiro, data) {
    const response = await api.put(`/v1/nfe-terceiro/${codnfeterceiro}`, data)
    return response.data
  },

  async manifestacao(codnfeterceiro, data) {
    const response = await api.post(`/v1/nfe-terceiro/${codnfeterceiro}/manifestacao`, data)
    return response.data
  },

  async download(codnfeterceiro) {
    const response = await api.post(`/v1/nfe-terceiro/${codnfeterceiro}/download`)
    return response.data
  },

  async revisao(codnfeterceiro) {
    const response = await api.post(`/v1/nfe-terceiro/${codnfeterceiro}/revisao`)
    return response.data
  },

  async conferencia(codnfeterceiro) {
    const response = await api.post(`/v1/nfe-terceiro/${codnfeterceiro}/conferencia`)
    return response.data
  },

  // ICMS-ST
  async icmsst(codnfeterceiro) {
    const response = await api.get(`/v1/nfe-terceiro/${codnfeterceiro}/icmsst`)
    return response.data
  },

  async gerarGuiaSt(codnfeterceiro, data) {
    const response = await api.post(`/v1/nfe-terceiro/${codnfeterceiro}/gerar-guia-st`, data)
    return response.data
  },

  guiaStPdfUrl(codnfeterceiro, codtitulonfeterceiro) {
    return `${process.env.API_URL}/v1/nfe-terceiro/${codnfeterceiro}/guia-st/${codtitulonfeterceiro}/pdf`
  },

  // Importação
  async validarImportacao(codnfeterceiro) {
    const response = await api.get(`/v1/nfe-terceiro/${codnfeterceiro}/validar-importacao`)
    return response.data
  },

  async importar(codnfeterceiro) {
    const response = await api.post(`/v1/nfe-terceiro/${codnfeterceiro}/importar`)
    return response.data
  },

  // Operações sobre itens
  async buscarItem(codnfeterceiro, barras) {
    const response = await api.post(`/v1/nfe-terceiro/${codnfeterceiro}/buscar-item`, { barras })
    return response.data
  },

  async updateItem(codnfeterceiro, codnfeterceiroitem, data) {
    const response = await api.put(
      `/v1/nfe-terceiro/${codnfeterceiro}/item/${codnfeterceiroitem}`,
      data,
    )
    return response.data
  },

  async conferenciaItem(codnfeterceiro, codnfeterceiroitem) {
    const response = await api.post(
      `/v1/nfe-terceiro/${codnfeterceiro}/item/${codnfeterceiroitem}/conferencia`,
    )
    return response.data
  },

  async dividirItem(codnfeterceiro, codnfeterceiroitem, parcelas) {
    const response = await api.post(
      `/v1/nfe-terceiro/${codnfeterceiro}/item/${codnfeterceiroitem}/dividir`,
      { parcelas },
    )
    return response.data
  },

  async marcarTipoProduto(codnfeterceiro, codtipoproduto) {
    const response = await api.post(`/v1/nfe-terceiro/${codnfeterceiro}/marcar-tipo-produto`, {
      codtipoproduto,
    })
    return response.data
  },

  async informarComplemento(codnfeterceiro, valor) {
    const response = await api.post(`/v1/nfe-terceiro/${codnfeterceiro}/informar-complemento`, {
      valor,
    })
    return response.data
  },

  async uploadXml(file) {
    const formData = new FormData()
    formData.append('xml', file)
    const response = await api.post('/v1/nfe-terceiro/upload-xml', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data
  },

  xml(codnfeterceiro) {
    return `${process.env.API_URL}/v1/nfe-terceiro/${codnfeterceiro}/xml`
  },

  danfe(codnfeterceiro) {
    return `${process.env.API_URL}/v1/nfe-terceiro/${codnfeterceiro}/danfe`
  },
}

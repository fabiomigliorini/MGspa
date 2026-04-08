import { defineStore } from 'pinia'
import api from '../services/api'

const BASE_URL = '/v1/nota-fiscal/controle'

export const useControleStore = defineStore('controle', {
  state: () => ({
    negociosSemNfce: [],
    loadingNegocios: false,
    gerandoNfce: false,
    gerandoTransferencias: false,
    resultadoNfce: null,
    resultadoTransferencias: {},
  }),

  actions: {
    async fetchNegociosSemNfce(codfilial = null) {
      this.loadingNegocios = true
      try {
        const params = {}
        if (codfilial) params.codfilial = codfilial
        const response = await api.get(`${BASE_URL}/negocios-sem-nfce`, { params })
        this.negociosSemNfce = response.data
        return this.negociosSemNfce
      } catch (error) {
        console.error('Erro ao buscar negócios sem NFC-e:', error)
        throw error
      } finally {
        this.loadingNegocios = false
      }
    },

    async gerarNfceFaltantes(codnegocioIds) {
      this.gerandoNfce = true
      this.resultadoNfce = null
      try {
        const response = await api.post(`${BASE_URL}/gerar-nfce-faltantes`, {
          codnegocio_ids: codnegocioIds,
        })
        this.resultadoNfce = response.data
        return response.data
      } catch (error) {
        console.error('Erro ao gerar NFC-e faltantes:', error)
        throw error
      } finally {
        this.gerandoNfce = false
      }
    },

    async gerarTransferencias(codfilial) {
      try {
        const response = await api.post(`${BASE_URL}/gerar-transferencias`, {
          codfilial,
        })
        this.resultadoTransferencias[codfilial] = response.data
        return response.data
      } catch (error) {
        console.error(`Erro ao gerar transferências para filial ${codfilial}:`, error)
        throw error
      }
    },

    resetResultados() {
      this.resultadoNfce = null
      this.resultadoTransferencias = {}
    },
  },
})

import { defineStore } from 'pinia'
import { api } from 'src/boot/axios'

export const useSelectNaturezaOperacaoStore = defineStore('selectNaturezaOperacao', {
  state: () => ({
    // Cache de natureza de operação por busca
    naturezaOperacaoCache: {},
  }),

  getters: {},

  actions: {
    /**
     * Busca natureza de operação por texto
     */
    async search(busca) {
      if (!busca || busca.length < 2) {
        return []
      }

      // Verifica cache
      const cacheKey = busca.toLowerCase()
      if (this.naturezaOperacaoCache[cacheKey]) {
        return this.naturezaOperacaoCache[cacheKey]
      }

      try {
        const response = await api.get('/select/natureza-operacao', {
          params: { busca },
        })
        const naturezas = response.data.map((item) => ({
          label: item.naturezaoperacao,
          value: item.codnaturezaoperacao,
          operacao: item.operacao,
        }))

        // Salva no cache
        this.naturezaOperacaoCache[cacheKey] = naturezas
        return naturezas
      } catch (error) {
        console.error('Erro ao buscar natureza de operação:', error)
        throw error
      }
    },

    /**
     * Busca uma natureza de operação específica por código
     */
    async fetch(codnaturezaoperacao) {
      try {
        const response = await api.get('/select/natureza-operacao', {
          params: { codnaturezaoperacao },
        })
        if (response.data.length > 0) {
          const item = response.data[0]
          return {
            label: item.naturezaoperacao,
            value: item.codnaturezaoperacao,
            operacao: item.operacao,
          }
        }
        return null
      } catch (error) {
        console.error('Erro ao buscar natureza de operação:', error)
        throw error
      }
    },

    /**
     * Limpa o cache de natureza de operação
     */
    clear() {
      this.naturezaOperacaoCache = {}
    },
  },
})

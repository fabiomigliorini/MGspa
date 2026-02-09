import { defineStore } from 'pinia'
import { api } from 'src/services/api'

export const useSelectNaturezaOperacaoStore = defineStore('selectNaturezaOperacao', {
  state: () => ({
    // Cache de natureza de operação por busca
    naturezaOperacaoCache: {},
    // Cache acumulativo de naturezas já carregadas por código
    naturezasById: {},
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
        const response = await api.get('v1/select/natureza-operacao', {
          params: { busca },
        })
        const naturezas = response.data.map((item) => ({
          label: item.naturezaoperacao,
          value: item.codnaturezaoperacao,
          operacao: item.operacao,
        }))

        // Salva no cache de busca
        this.naturezaOperacaoCache[cacheKey] = naturezas

        // Adiciona cada natureza ao cache por ID
        naturezas.forEach((natureza) => {
          this.naturezasById[natureza.value] = natureza
        })

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
      // Verifica se já está no cache
      if (this.naturezasById[codnaturezaoperacao]) {
        return this.naturezasById[codnaturezaoperacao]
      }

      try {
        const response = await api.get('v1/select/natureza-operacao', {
          params: { codnaturezaoperacao },
        })
        if (response.data.length > 0) {
          const item = response.data[0]
          const natureza = {
            label: item.naturezaoperacao,
            value: item.codnaturezaoperacao,
            operacao: item.operacao,
          }

          // Adiciona ao cache
          this.naturezasById[codnaturezaoperacao] = natureza
          return natureza
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
      this.naturezasById = {}
    },
  },
})

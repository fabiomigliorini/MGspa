import { defineStore } from 'pinia'
import { api } from 'src/boot/axios'

export const useSelectTipoProdutoStore = defineStore('selectTipoProduto', {
  state: () => ({
    // Cache de tipo de produto por busca
    tipoProdutoCache: {},
  }),

  getters: {},

  actions: {
    /**
     * Busca tipo de produto por texto
     */
    async search(busca) {
      if (!busca || busca.length < 2) {
        return []
      }

      // Verifica cache
      const cacheKey = busca.toLowerCase()
      if (this.tipoProdutoCache[cacheKey]) {
        return this.tipoProdutoCache[cacheKey]
      }

      try {
        const response = await api.get('v1/select/tipo-produto', {
          params: { busca },
        })
        const tipos = response.data.map((item) => ({
          label: item.tipoproduto,
          value: item.codtipoproduto,
        }))

        // Salva no cache
        this.tipoProdutoCache[cacheKey] = tipos
        return tipos
      } catch (error) {
        console.error('Erro ao buscar tipo de produto:', error)
        throw error
      }
    },

    /**
     * Busca um tipo de produto específico por código
     */
    async fetch(codtipoproduto) {
      try {
        const response = await api.get('v1/select/tipo-produto', {
          params: { codtipoproduto },
        })
        if (response.data.length > 0) {
          const item = response.data[0]
          return {
            label: item.tipoproduto,
            value: item.codtipoproduto,
          }
        }
        return null
      } catch (error) {
        console.error('Erro ao buscar tipo de produto:', error)
        throw error
      }
    },

    /**
     * Limpa o cache de tipo de produto
     */
    clear() {
      this.tipoProdutoCache = {}
    },
  },
})

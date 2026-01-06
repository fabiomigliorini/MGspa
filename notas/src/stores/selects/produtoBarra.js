import { defineStore } from 'pinia'
import { api } from 'src/services/api'

export const useSelectProdutoBarraStore = defineStore('selectProdutoBarra', {
  state: () => ({
    // Cache de produtos por busca
    produtos: [],
    busca: null,
  }),

  getters: {},

  actions: {
    /**
     * Busca produto por texto
     */
    async search() {
      const busca = this.busca
      if (!busca || busca.length < 2) {
        return []
      }

      try {
        const response = await api.get('v1/select/produto-barra', {
          params: { busca },
        })
        this.produtos = response.data
        return this.produtos
      } catch (error) {
        console.error('Erro ao buscar produto:', error)
        throw error
      }
    },

    /**
     * Limpa o cache de produtos
     */
    clear() {
      this.produtos = []
    },
  },
})

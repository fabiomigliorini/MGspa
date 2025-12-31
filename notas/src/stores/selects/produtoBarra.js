import { defineStore } from 'pinia'
import { api } from 'src/boot/axios'

export const useSelectProdutoBarraStore = defineStore('selectProdutoBarra', {
  state: () => ({
    // Cache de produtos por busca
    produtoBarraCache: {},
    // Cache acumulativo de produtos já carregados por código
    produtosById: {},
  }),

  getters: {},

  actions: {
    /**
     * Busca produto por texto
     */
    async search(busca) {
      if (!busca || busca.length < 2) {
        return []
      }

      // Verifica cache
      const cacheKey = busca.toLowerCase()
      if (this.produtoBarraCache[cacheKey]) {
        return this.produtoBarraCache[cacheKey]
      }

      try {
        const response = await api.get('v1/select/produto-barra', {
          params: { busca },
        })
        const produtos = response.data.map((item) => ({
          label: item.descricao,
          value: item.codprodutobarra,
          descricao: item.descricao,
          codigobarra: item.codigobarra,
          referencia: item.referencia,
          unidade: item.unidade,
          preco: item.preco,
        }))

        // Salva no cache de busca
        this.produtoBarraCache[cacheKey] = produtos

        // Adiciona cada produto ao cache por ID
        produtos.forEach((produto) => {
          this.produtosById[produto.value] = produto
        })

        return produtos
      } catch (error) {
        console.error('Erro ao buscar produto:', error)
        throw error
      }
    },

    /**
     * Busca um produto específico por código
     */
    async fetch(codprodutobarra) {
      // Verifica se já está no cache
      if (this.produtosById[codprodutobarra]) {
        console.log('📦 Usando cache - Produto:', codprodutobarra)
        return this.produtosById[codprodutobarra]
      }

      console.log('🌐 Buscando da API - Produto:', codprodutobarra)
      try {
        const response = await api.get('v1/select/produto-barra', {
          params: { codprodutobarra },
        })
        if (response.data.length > 0) {
          const item = response.data[0]
          const produto = {
            label: item.descricao,
            value: item.codprodutobarra,
            descricao: item.descricao,
            codigobarra: item.codigobarra,
            referencia: item.referencia,
            unidade: item.unidade,
            preco: item.preco,
          }

          // Adiciona ao cache
          this.produtosById[codprodutobarra] = produto
          return produto
        }
        return null
      } catch (error) {
        console.error('Erro ao buscar produto:', error)
        throw error
      }
    },

    /**
     * Limpa o cache de produtos
     */
    clear() {
      this.produtoBarraCache = {}
      this.produtosById = {}
    },
  },
})

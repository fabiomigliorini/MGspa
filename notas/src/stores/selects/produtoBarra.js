import { defineStore } from 'pinia'
import { api } from 'src/services/api'

function mapProduto(raw) {
  if (!raw) return null
  return {
    value: raw.codprodutobarra,
    label: raw.barras ? `${raw.barras} - ${raw.descricao}` : raw.descricao,
    descricao: raw.descricao,
    codigobarra: raw.barras,
    preco: raw.preco,
    sigla: raw.sigla,
    codproduto: raw.codproduto,
    marca: raw.marca,
    referencia: raw.referencia,
    inativo: raw.inativo,
    imagem: raw.imagem,
  }
}

export const useSelectProdutoBarraStore = defineStore('selectProdutoBarra', {
  state: () => ({
    // Cache legado usado por NotaFiscalItemDialog (dados crus)
    produtos: [],
    busca: null,
  }),

  getters: {},

  actions: {
    /**
     * Busca produtos por texto.
     * - Com argumento: retorna array mapeado para SelectProdutoBarra (não toca no state).
     * - Sem argumento: usa `this.busca` e popula `this.produtos` (modo legado).
     */
    async search(val) {
      const useStateInput = val === undefined
      const busca = useStateInput ? this.busca : val

      if (!busca || busca.length < 2) {
        if (useStateInput) this.produtos = []
        return []
      }

      try {
        const response = await api.get('v1/select/produto-barra', {
          params: { busca },
        })
        if (useStateInput) {
          this.produtos = response.data
          return this.produtos
        }
        return response.data.map(mapProduto)
      } catch (error) {
        console.error('Erro ao buscar produto:', error)
        throw error
      }
    },

    /**
     * Carrega um único produto por codprodutobarra (para inicialização de selects).
     */
    async fetch(codprodutobarra) {
      if (!codprodutobarra) return null
      try {
        const response = await api.get('v1/select/produto-barra', {
          params: { codprodutobarra },
        })
        if (!response.data || response.data.length === 0) return null
        return mapProduto(response.data[0])
      } catch (error) {
        console.error('Erro ao carregar produto:', error)
        throw error
      }
    },

    /**
     * Limpa o cache de produtos (modo legado)
     */
    clear() {
      this.produtos = []
      this.busca = null
    },
  },
})

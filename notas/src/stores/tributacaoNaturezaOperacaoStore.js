import { defineStore } from 'pinia'
import tributacaoNaturezaOperacaoService from '../services/tributacaoNaturezaOperacaoService'

// Constantes para Tipo de Produto
export const TIPO_PRODUTO_OPTIONS = [
  { value: 0, label: 'Mercadoria' },
  { value: 1, label: 'Matéria Prima' },
  { value: 2, label: 'Produto Intermediário' },
  { value: 3, label: 'Produto em Fabricação' },
  { value: 4, label: 'Produto Acabado' },
  { value: 5, label: 'Embalagem' },
  { value: 6, label: 'Subproduto' },
  { value: 7, label: 'Material de Uso e Consumo' },
  { value: 8, label: 'Ativo Imobilizado' },
  { value: 9, label: 'Serviços' },
  { value: 10, label: 'Outros Insumos' },
  { value: 99, label: 'Outras' },
]

export const useTributacaoNaturezaOperacaoStore = defineStore('tributacaoNaturezaOperacao', {
  state: () => ({
    // Código da natureza de operação pai
    codnaturezaoperacao: null,

    // Lista de Tributações
    tributacoes: [],
    pagination: {
      page: 1,
      perPage: 9999,
      hasMore: true,
      loading: false,
      total: 0,
    },
    filters: {
      codtributacaonaturezaoperacao: null,
      codestado: null,
      codtributacao: null,
      codtipoproduto: null,
      ncm: null,
      codcfop: null,
      bit: null,
    },
    initialLoadDone: false,

    // Tributação atual (para edição)
    currentTributacao: null,

    // Loading states
    loading: {
      tributacao: false,
    },
  }),

  getters: {
    hasActiveFilters: (state) => {
      return Object.values(state.filters).some((value) => value !== null && value !== '')
    },

    activeFiltersCount: (state) => {
      return Object.values(state.filters).filter((value) => value !== null && value !== '').length
    },

    getTipoProdutoLabel: () => (codtipoproduto) => {
      const option = TIPO_PRODUTO_OPTIONS.find((o) => o.value === codtipoproduto)
      return option ? option.label : '-'
    },
  },

  actions: {
    setCodNaturezaOperacao(codnaturezaoperacao) {
      if (this.codnaturezaoperacao !== codnaturezaoperacao) {
        this.codnaturezaoperacao = codnaturezaoperacao
        this.resetList()
      }
    },

    resetList() {
      this.tributacoes = []
      this.pagination.page = 1
      this.pagination.hasMore = true
      this.initialLoadDone = false
    },

    async fetchTributacoes(reset = false) {
      if (!this.codnaturezaoperacao) return

      if (this.pagination.loading || (!reset && !this.pagination.hasMore)) {
        return
      }

      if (reset) {
        this.tributacoes = []
        this.pagination.page = 1
        this.pagination.hasMore = true
      }

      try {
        this.pagination.loading = true

        // Filtra apenas os filtros com valor definido (não null/undefined/string vazia)
        // Mas mantém valores booleanos false
        const activeFilters = Object.entries(this.filters).reduce((acc, [key, value]) => {
          if (value !== null && value !== undefined && value !== '') {
            acc[key] = value
          } else if (value === false) {
            acc[key] = value
          }
          return acc
        }, {})

        const params = {
          ...activeFilters,
          page: this.pagination.page,
          per_page: this.pagination.perPage,
        }

        const response = await tributacaoNaturezaOperacaoService.list(
          this.codnaturezaoperacao,
          params,
        )

        const newTributacoes = response.data || []

        if (reset) {
          this.tributacoes = newTributacoes
        } else {
          this.tributacoes.push(...newTributacoes)
        }

        this.pagination.total = response.meta.total
        this.pagination.hasMore = response.meta.current_page < response.meta.last_page

        if (this.pagination.hasMore) {
          this.pagination.page++
        }

        this.initialLoadDone = true
        return response
      } catch (error) {
        console.error('Erro ao buscar Tributações:', error)
        throw error
      } finally {
        this.pagination.loading = false
      }
    },

    async fetchTributacao(codtributacaonaturezaoperacao) {
      if (!this.codnaturezaoperacao) return

      const codNum = parseInt(codtributacaonaturezaoperacao)

      if (
        this.currentTributacao &&
        this.currentTributacao.codtributacaonaturezaoperacao === codNum
      ) {
        return this.currentTributacao
      }

      this.loading.tributacao = true
      try {
        const response = await tributacaoNaturezaOperacaoService.get(
          this.codnaturezaoperacao,
          codNum,
        )
        this.currentTributacao = response.data
        return response.data
      } catch (error) {
        console.error('Erro ao buscar Tributação:', error)
        throw error
      } finally {
        this.loading.tributacao = false
      }
    },

    async createTributacao(data) {
      if (!this.codnaturezaoperacao) return

      this.loading.tributacao = true
      try {
        const response = await tributacaoNaturezaOperacaoService.create(
          this.codnaturezaoperacao,
          data,
        )
        this.currentTributacao = response.data
        this.syncCurrentTributacaoToList()
        return response.data
      } catch (error) {
        console.error('Erro ao criar Tributação:', error)
        throw error
      } finally {
        this.loading.tributacao = false
      }
    },

    async updateTributacao(codtributacaonaturezaoperacao, data) {
      if (!this.codnaturezaoperacao) return

      this.loading.tributacao = true
      try {
        const response = await tributacaoNaturezaOperacaoService.update(
          this.codnaturezaoperacao,
          codtributacaonaturezaoperacao,
          data,
        )
        this.currentTributacao = response.data
        this.syncCurrentTributacaoToList()
        return response.data
      } catch (error) {
        console.error('Erro ao atualizar Tributação:', error)
        throw error
      } finally {
        this.loading.tributacao = false
      }
    },

    async deleteTributacao(codtributacaonaturezaoperacao) {
      if (!this.codnaturezaoperacao) return

      this.loading.tributacao = true
      try {
        await tributacaoNaturezaOperacaoService.delete(
          this.codnaturezaoperacao,
          codtributacaonaturezaoperacao,
        )

        this.tributacoes = this.tributacoes.filter(
          (t) => t.codtributacaonaturezaoperacao !== codtributacaonaturezaoperacao,
        )
        this.pagination.total--

        if (this.currentTributacao?.codtributacaonaturezaoperacao === codtributacaonaturezaoperacao) {
          this.clearCurrentTributacao()
        }
      } catch (error) {
        console.error('Erro ao excluir Tributação:', error)
        throw error
      } finally {
        this.loading.tributacao = false
      }
    },

    setFilters(filters) {
      this.filters = { ...this.filters, ...filters }
      this.initialLoadDone = false
    },

    clearFilters() {
      this.filters = {
        codestado: null,
        codtributacao: null,
        codtipoproduto: null,
        ncm: null,
        codcfop: null,
        bit: null,
      }
      this.initialLoadDone = false
    },

    clearCurrentTributacao() {
      this.currentTributacao = null
    },

    syncCurrentTributacaoToList() {
      if (!this.currentTributacao) return

      const index = this.tributacoes.findIndex(
        (t) =>
          t.codtributacaonaturezaoperacao === this.currentTributacao.codtributacaonaturezaoperacao,
      )
      if (index !== -1) {
        this.tributacoes[index] = { ...this.currentTributacao }
      } else {
        this.tributacoes.unshift({ ...this.currentTributacao })
        this.pagination.total++
      }
    },
  },
})

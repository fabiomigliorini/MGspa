import { defineStore } from 'pinia'
import dfeDistribuicaoService from '../services/dfeDistribuicaoService'

export const useDfeDistribuicaoStore = defineStore('dfeDistribuicao', {
  persist: {
    pick: ['filters'],
  },
  state: () => ({
    items: [],
    pagination: {
      page: 1,
      perPage: 50,
      hasMore: true,
      loading: false,
      total: 0,
    },
    filters: {
      codfilial: null,
      datade: null,
      dataate: null,
      nfechave: null,
      nsude: null,
      nsuate: null,
    },
    initialLoadDone: false,
  }),

  getters: {
    hasActiveFilters: (state) => {
      return Object.values(state.filters).some((value) => value !== null && value !== '')
    },

    activeFiltersCount: (state) => {
      return Object.values(state.filters).filter((value) => value !== null && value !== '').length
    },
  },

  actions: {
    async fetchItems(reset = false) {
      if (this.pagination.loading || (!reset && !this.pagination.hasMore)) {
        return
      }

      if (reset) {
        this.items = []
        this.pagination.page = 1
        this.pagination.hasMore = true
      }

      try {
        this.pagination.loading = true

        const params = {
          ...this.filters,
          page: this.pagination.page,
        }

        const response = await dfeDistribuicaoService.list(params)

        const newItems = response.data || []

        if (reset) {
          this.items = newItems
        } else {
          this.items.push(...newItems)
        }

        this.pagination.hasMore = newItems.length > 0

        if (this.pagination.hasMore) {
          this.pagination.page++
        }

        this.initialLoadDone = true

        return response
      } catch (error) {
        console.error('Erro ao buscar distribuições DFe:', error)
        throw error
      } finally {
        this.pagination.loading = false
      }
    },

    setFilters(filters) {
      this.filters = { ...this.filters, ...filters }
      this.initialLoadDone = false
    },

    clearFilters() {
      this.filters = {
        codfilial: null,
        datade: null,
        dataate: null,
        nfechave: null,
        nsude: null,
        nsuate: null,
      }
      this.initialLoadDone = false
    },
  },
})

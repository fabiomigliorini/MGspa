import { defineStore } from 'pinia'
import cfopService from '../services/cfopService'

export const useCfopStore = defineStore('cfop', {
  persist: {
    pick: ['filters'],
  },
  state: () => ({
    // Lista de CFOPs
    cfops: [],
    pagination: {
      page: 1,
      perPage: 20,
      hasMore: true,
      loading: false,
      total: 0,
    },
    filters: {
      cfop: null,
      descricao: null,
    },
    // Flag para controlar se já foi carregado
    initialLoadDone: false,

    // CFOP atual (para edição)
    currentCfop: null,

    // Loading states
    loading: {
      cfop: false,
    },
  }),

  getters: {
    // Verifica se há filtros ativos
    hasActiveFilters: (state) => {
      return Object.values(state.filters).some((value) => value !== null && value !== '')
    },

    // Conta quantos filtros estão ativos
    activeFiltersCount: (state) => {
      return Object.values(state.filters).filter((value) => value !== null && value !== '').length
    },
  },

  actions: {
    async fetchCfops(reset = false) {
      // Se já está carregando ou não tem mais dados, retorna
      if (this.pagination.loading || (!reset && !this.pagination.hasMore)) {
        return
      }

      // Se é reset, limpa os dados
      if (reset) {
        this.cfops = []
        this.pagination.page = 1
        this.pagination.hasMore = true
      }

      try {
        this.pagination.loading = true

        const params = {
          ...this.filters,
          page: this.pagination.page,
          per_page: this.pagination.perPage,
        }

        const response = await cfopService.list(params)

        const newCfops = response.data || []

        // Adiciona novos CFOPs à lista existente
        if (reset) {
          this.cfops = newCfops
        } else {
          this.cfops.push(...newCfops)
        }

        // Atualiza paginação
        this.pagination.total = response.meta.total
        this.pagination.hasMore = response.meta.current_page < response.meta.last_page

        // Incrementa página para próxima busca
        if (this.pagination.hasMore) {
          this.pagination.page++
        }

        this.initialLoadDone = true

        return response
      } catch (error) {
        console.error('Erro ao buscar CFOPs:', error)
        throw error
      } finally {
        this.pagination.loading = false
      }
    },

    async fetchCfop(codcfop) {
      // Converte para número para garantir comparação correta
      const codcfopNum = parseInt(codcfop)

      // Verifica se o CFOP já está carregado na store E é o mesmo CFOP
      if (this.currentCfop && this.currentCfop.codcfop === codcfopNum) {
        // CFOP já está carregado, retorna sem fazer nova requisição
        return this.currentCfop
      }

      this.loading.cfop = true
      try {
        const response = await cfopService.get(codcfopNum)
        this.currentCfop = response.data
        this.syncCurrentCfopToList()
        return response.data
      } catch (error) {
        console.error('Erro ao buscar CFOP:', error)
        throw error
      } finally {
        this.loading.cfop = false
      }
    },

    async createCfop(data) {
      this.loading.cfop = true
      try {
        const response = await cfopService.create(data)
        this.currentCfop = response.data
        this.syncCurrentCfopToList()

        return response.data
      } catch (error) {
        console.error('Erro ao criar CFOP:', error)
        throw error
      } finally {
        this.loading.cfop = false
      }
    },

    async updateCfop(codcfop, data) {
      this.loading.cfop = true
      try {
        const response = await cfopService.update(codcfop, data)
        this.currentCfop = response.data
        this.syncCurrentCfopToList()

        return response.data
      } catch (error) {
        console.error('Erro ao atualizar CFOP:', error)
        throw error
      } finally {
        this.loading.cfop = false
      }
    },

    async deleteCfop(codcfop) {
      this.loading.cfop = true
      try {
        await cfopService.delete(codcfop)

        // Remove da lista
        this.cfops = this.cfops.filter((c) => c.codcfop !== codcfop)
        this.pagination.total--

        // Limpa o CFOP atual se for o mesmo
        if (this.currentCfop?.codcfop === codcfop) {
          this.clearCurrentCfop()
        }
      } catch (error) {
        console.error('Erro ao excluir CFOP:', error)
        throw error
      } finally {
        this.loading.cfop = false
      }
    },

    setFilters(filters) {
      this.filters = { ...this.filters, ...filters }
      // Reset quando altera filtros
      this.initialLoadDone = false
    },

    clearFilters() {
      this.filters = {
        cfop: null,
        descricao: null,
      }
      this.initialLoadDone = false
    },

    clearCurrentCfop() {
      this.currentCfop = null
    },

    /**
     * Sincroniza currentCfop com a lista cfops
     * Deve ser chamado sempre que currentCfop for atualizado
     */
    syncCurrentCfopToList() {
      if (!this.currentCfop) return

      const index = this.cfops.findIndex((c) => c.codcfop === this.currentCfop.codcfop)
      if (index !== -1) {
        this.cfops[index] = { ...this.currentCfop }
      } else {
        // Insere na posição correta (ordenado por codcfop)
        const newCfop = { ...this.currentCfop }
        const insertIndex = this.cfops.findIndex((c) => c.codcfop > newCfop.codcfop)
        if (insertIndex === -1) {
          // É maior que todos, adiciona no final
          this.cfops.push(newCfop)
        } else {
          // Insere na posição correta
          this.cfops.splice(insertIndex, 0, newCfop)
        }
        this.pagination.total++
      }
    },
  },
})

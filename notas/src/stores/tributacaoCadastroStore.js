import { defineStore } from 'pinia'
import tributacaoCadastroService from '../services/tributacaoCadastroService'

export const useTributacaoCadastroStore = defineStore('tributacaoCadastro', {
  persist: {
    pick: ['filters'],
  },
  state: () => ({
    // Lista de Tributacoes
    tributacoes: [],
    pagination: {
      page: 1,
      perPage: 9999,
      hasMore: true,
      loading: false,
      total: 0,
    },
    filters: {
      tributacao: null,
    },
    // Flag para controlar se ja foi carregado
    initialLoadDone: false,

    // Tributacao atual (para edicao)
    currentTributacao: null,

    // Loading states
    loading: {
      tributacao: false,
    },
  }),

  getters: {
    // Verifica se ha filtros ativos
    hasActiveFilters: (state) => {
      return Object.values(state.filters).some((value) => value !== null && value !== '')
    },

    // Conta quantos filtros estao ativos
    activeFiltersCount: (state) => {
      return Object.values(state.filters).filter((value) => value !== null && value !== '').length
    },
  },

  actions: {
    async fetchTributacoes(reset = false) {
      // Se ja esta carregando ou nao tem mais dados, retorna
      if (this.pagination.loading || (!reset && !this.pagination.hasMore)) {
        return
      }

      // Se e reset, limpa os dados
      if (reset) {
        this.tributacoes = []
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

        const response = await tributacaoCadastroService.list(params)

        const newTributacoes = response.data || []

        // Adiciona novas Tributacoes a lista existente
        if (reset) {
          this.tributacoes = newTributacoes
        } else {
          this.tributacoes.push(...newTributacoes)
        }

        // Atualiza paginacao
        this.pagination.total = response.meta.total
        this.pagination.hasMore = response.meta.current_page < response.meta.last_page

        // Incrementa pagina para proxima busca
        if (this.pagination.hasMore) {
          this.pagination.page++
        }

        this.initialLoadDone = true

        return response
      } catch (error) {
        console.error('Erro ao buscar Tributacoes:', error)
        throw error
      } finally {
        this.pagination.loading = false
      }
    },

    async fetchTributacao(codtributacao) {
      // Converte para numero para garantir comparacao correta
      const codtributacaoNum = parseInt(codtributacao)

      // Verifica se a Tributacao ja esta carregada na store E e a mesma Tributacao
      if (this.currentTributacao && this.currentTributacao.codtributacao === codtributacaoNum) {
        // Tributacao ja esta carregada, retorna sem fazer nova requisicao
        return this.currentTributacao
      }

      this.loading.tributacao = true
      try {
        const response = await tributacaoCadastroService.get(codtributacaoNum)
        this.currentTributacao = response.data
        this.syncCurrentTributacaoToList()
        return response.data
      } catch (error) {
        console.error('Erro ao buscar Tributacao:', error)
        throw error
      } finally {
        this.loading.tributacao = false
      }
    },

    async createTributacao(data) {
      this.loading.tributacao = true
      try {
        const response = await tributacaoCadastroService.create(data)
        this.currentTributacao = response.data
        this.syncCurrentTributacaoToList()

        return response.data
      } catch (error) {
        console.error('Erro ao criar Tributacao:', error)
        throw error
      } finally {
        this.loading.tributacao = false
      }
    },

    async updateTributacao(codtributacao, data) {
      this.loading.tributacao = true
      try {
        const response = await tributacaoCadastroService.update(codtributacao, data)
        this.currentTributacao = response.data
        this.syncCurrentTributacaoToList()

        return response.data
      } catch (error) {
        console.error('Erro ao atualizar Tributacao:', error)
        throw error
      } finally {
        this.loading.tributacao = false
      }
    },

    async deleteTributacao(codtributacao) {
      this.loading.tributacao = true
      try {
        await tributacaoCadastroService.delete(codtributacao)

        // Remove da lista
        this.tributacoes = this.tributacoes.filter((t) => t.codtributacao !== codtributacao)
        this.pagination.total--

        // Limpa a Tributacao atual se for a mesma
        if (this.currentTributacao?.codtributacao === codtributacao) {
          this.clearCurrentTributacao()
        }
      } catch (error) {
        console.error('Erro ao excluir Tributacao:', error)
        throw error
      } finally {
        this.loading.tributacao = false
      }
    },

    setFilters(filters) {
      this.filters = { ...this.filters, ...filters }
      // Reset quando altera filtros
      this.initialLoadDone = false
    },

    clearFilters() {
      this.filters = {
        tributacao: null,
      }
      this.initialLoadDone = false
    },

    clearCurrentTributacao() {
      this.currentTributacao = null
    },

    /**
     * Sincroniza currentTributacao com a lista tributacoes
     * Deve ser chamado sempre que currentTributacao for atualizado
     */
    syncCurrentTributacaoToList() {
      if (!this.currentTributacao) return

      const index = this.tributacoes.findIndex(
        (t) => t.codtributacao === this.currentTributacao.codtributacao,
      )
      if (index !== -1) {
        this.tributacoes[index] = { ...this.currentTributacao }
      } else {
        // Insere na posicao correta (ordenado por codtributacao)
        const newTributacao = { ...this.currentTributacao }
        const insertIndex = this.tributacoes.findIndex(
          (t) => t.codtributacao > newTributacao.codtributacao,
        )
        if (insertIndex === -1) {
          // E maior que todos, adiciona no final
          this.tributacoes.push(newTributacao)
        } else {
          // Insere na posicao correta
          this.tributacoes.splice(insertIndex, 0, newTributacao)
        }
        this.pagination.total++
      }
    },
  },
})

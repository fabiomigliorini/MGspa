import { defineStore } from 'pinia'
import naturezaOperacaoService from '../services/naturezaOperacaoService'

// Constantes para Finalidade NFe
export const FINNFE_OPTIONS = [
  { value: 1, label: 'Normal' },
  { value: 2, label: 'Complementar' },
  { value: 3, label: 'Ajuste' },
  { value: 4, label: 'Devolução / Retorno' },
]

// Constantes para Operação (Entrada/Saída)
export const OPERACAO_OPTIONS = [
  { value: 1, label: 'Entrada' },
  { value: 2, label: 'Saída' },
]

// Constantes para Tipo de Preço
export const PRECO_OPTIONS = [
  { value: 1, label: 'Negócio' },
  { value: 2, label: 'Transferência' },
  { value: 3, label: 'Custo' },
  { value: 4, label: 'Origem' },
]

export const useNaturezaOperacaoStore = defineStore('naturezaOperacao', {
  persist: {
    pick: ['filters'],
  },
  state: () => ({
    // Lista de Naturezas de Operação
    naturezaOperacoes: [],
    pagination: {
      page: 1,
      perPage: 9999,
      hasMore: true,
      loading: false,
      total: 0,
    },
    filters: {
      naturezaoperacao: null,
      finnfe: null,
      codoperacao: null,
      emitida: null,
    },
    // Flag para controlar se ja foi carregado
    initialLoadDone: false,

    // Natureza de Operação atual (para edicao)
    currentNaturezaOperacao: null,

    // Loading states
    loading: {
      naturezaOperacao: false,
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

    // Retorna label da finalidade NFe
    getFinnfeLabel: () => (finnfe) => {
      const option = FINNFE_OPTIONS.find((o) => o.value === finnfe)
      return option ? option.label : '-'
    },

    // Retorna label da operação
    getOperacaoLabel: () => (codoperacao) => {
      const option = OPERACAO_OPTIONS.find((o) => o.value === codoperacao)
      return option ? option.label : '-'
    },
  },

  actions: {
    async fetchNaturezaOperacoes(reset = false) {
      // Se ja esta carregando ou nao tem mais dados, retorna
      if (this.pagination.loading || (!reset && !this.pagination.hasMore)) {
        return
      }

      // Se e reset, limpa os dados
      if (reset) {
        this.naturezaOperacoes = []
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

        const response = await naturezaOperacaoService.list(params)

        const newNaturezaOperacoes = response.data || []

        // Adiciona novas Naturezas a lista existente
        if (reset) {
          this.naturezaOperacoes = newNaturezaOperacoes
        } else {
          this.naturezaOperacoes.push(...newNaturezaOperacoes)
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
        console.error('Erro ao buscar Naturezas de Operação:', error)
        throw error
      } finally {
        this.pagination.loading = false
      }
    },

    async fetchNaturezaOperacao(codnaturezaoperacao) {
      // Converte para numero para garantir comparacao correta
      const codnaturezaoperacaoNum = parseInt(codnaturezaoperacao)

      // Verifica se a Natureza ja esta carregada na store E e a mesma
      if (
        this.currentNaturezaOperacao &&
        this.currentNaturezaOperacao.codnaturezaoperacao === codnaturezaoperacaoNum
      ) {
        return this.currentNaturezaOperacao
      }

      this.loading.naturezaOperacao = true
      try {
        const response = await naturezaOperacaoService.get(codnaturezaoperacaoNum)
        this.currentNaturezaOperacao = response.data
        this.syncCurrentNaturezaOperacaoToList()
        return response.data
      } catch (error) {
        console.error('Erro ao buscar Natureza de Operação:', error)
        throw error
      } finally {
        this.loading.naturezaOperacao = false
      }
    },

    async createNaturezaOperacao(data) {
      this.loading.naturezaOperacao = true
      try {
        const response = await naturezaOperacaoService.create(data)
        this.currentNaturezaOperacao = response.data
        this.syncCurrentNaturezaOperacaoToList()

        return response.data
      } catch (error) {
        console.error('Erro ao criar Natureza de Operação:', error)
        throw error
      } finally {
        this.loading.naturezaOperacao = false
      }
    },

    async updateNaturezaOperacao(codnaturezaoperacao, data) {
      this.loading.naturezaOperacao = true
      try {
        const response = await naturezaOperacaoService.update(codnaturezaoperacao, data)
        this.currentNaturezaOperacao = response.data
        this.syncCurrentNaturezaOperacaoToList()

        return response.data
      } catch (error) {
        console.error('Erro ao atualizar Natureza de Operação:', error)
        throw error
      } finally {
        this.loading.naturezaOperacao = false
      }
    },

    async deleteNaturezaOperacao(codnaturezaoperacao) {
      codnaturezaoperacao = parseInt(codnaturezaoperacao)
      this.loading.naturezaOperacao = true
      try {
        await naturezaOperacaoService.delete(codnaturezaoperacao)

        // Remove da lista
        this.naturezaOperacoes = this.naturezaOperacoes.filter(
          (n) => n.codnaturezaoperacao !== codnaturezaoperacao
        )
        this.pagination.total--

        // Limpa a Natureza atual se for a mesma
        if (this.currentNaturezaOperacao?.codnaturezaoperacao === codnaturezaoperacao) {
          this.clearCurrentNaturezaOperacao()
        }
      } catch (error) {
        console.error('Erro ao excluir Natureza de Operação:', error)
        throw error
      } finally {
        this.loading.naturezaOperacao = false
      }
    },

    setFilters(filters) {
      this.filters = { ...this.filters, ...filters }
      // Reset quando altera filtros
      this.initialLoadDone = false
    },

    clearFilters() {
      this.filters = {
        naturezaoperacao: null,
        finnfe: null,
        codoperacao: null,
        emitida: null,
      }
      this.initialLoadDone = false
    },

    clearCurrentNaturezaOperacao() {
      this.currentNaturezaOperacao = null
    },

    /**
     * Sincroniza currentNaturezaOperacao com a lista naturezaOperacoes
     * Deve ser chamado sempre que currentNaturezaOperacao for atualizado
     */
    syncCurrentNaturezaOperacaoToList() {
      if (!this.currentNaturezaOperacao) return

      const index = this.naturezaOperacoes.findIndex(
        (n) => n.codnaturezaoperacao === this.currentNaturezaOperacao.codnaturezaoperacao
      )
      if (index !== -1) {
        this.naturezaOperacoes[index] = { ...this.currentNaturezaOperacao }
      } else {
        // Insere na posicao correta (ordenado por codnaturezaoperacao)
        const newNaturezaOperacao = { ...this.currentNaturezaOperacao }
        const insertIndex = this.naturezaOperacoes.findIndex(
          (n) => n.codnaturezaoperacao > newNaturezaOperacao.codnaturezaoperacao
        )
        if (insertIndex === -1) {
          // E maior que todos, adiciona no final
          this.naturezaOperacoes.push(newNaturezaOperacao)
        } else {
          // Insere na posicao correta
          this.naturezaOperacoes.splice(insertIndex, 0, newNaturezaOperacao)
        }
        this.pagination.total++
      }
    },
  },
})

import { defineStore } from 'pinia'
import nfeTerceiroService from '../services/nfeTerceiroService'

export const useNfeTerceiroStore = defineStore('nfeTerceiro', {
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
      codpessoa: null,
      codgrupoeconomico: null,
      codnaturezaoperacao: null,
      nfechave: null,
      emissao_inicio: null,
      emissao_fim: null,
      indsituacao: null,
      indmanifestacao: null,
      ignorada: null,
      revisao: null,
      conferencia: null,
      importacao: null,
    },
    initialLoadDone: false,
    currentNfeTerceiro: null,
    loading: {
      nfeTerceiro: false,
    },
  }),

  getters: {
    hasActiveFilters: (state) => {
      return Object.values(state.filters).some((value) => value !== null && value !== '')
    },

    activeFiltersCount: (state) => {
      return Object.values(state.filters).filter((value) => value !== null && value !== '').length
    },

    itens: (state) => state.currentNfeTerceiro?.itens || [],
    duplicatas: (state) => state.currentNfeTerceiro?.duplicatas || [],
    pagamentos: (state) => state.currentNfeTerceiro?.pagamentos || [],
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
          per_page: this.pagination.perPage,
        }

        const response = await nfeTerceiroService.list(params)
        const newItems = response.data || []

        if (reset) {
          this.items = newItems
        } else {
          this.items.push(...newItems)
        }

        this.pagination.total = response.meta?.total || 0
        this.pagination.hasMore = response.meta
          ? response.meta.current_page < response.meta.last_page
          : newItems.length > 0

        if (this.pagination.hasMore) {
          this.pagination.page++
        }

        this.initialLoadDone = true
        return response
      } catch (error) {
        console.error('Erro ao buscar NFe de terceiros:', error)
        throw error
      } finally {
        this.pagination.loading = false
      }
    },

    async fetchNfeTerceiro(codnfeterceiro) {
      const id = parseInt(codnfeterceiro)
      if (this.currentNfeTerceiro && this.currentNfeTerceiro.codnfeterceiro === id) {
        return this.currentNfeTerceiro
      }

      this.loading.nfeTerceiro = true
      try {
        const response = await nfeTerceiroService.get(id)
        this.currentNfeTerceiro = response.data
        this.syncCurrentToList()
        return response.data
      } catch (error) {
        console.error('Erro ao buscar NFe de terceiro:', error)
        throw error
      } finally {
        this.loading.nfeTerceiro = false
      }
    },

    async updateNfeTerceiro(codnfeterceiro, data) {
      this.loading.nfeTerceiro = true
      try {
        const response = await nfeTerceiroService.update(codnfeterceiro, data)
        this.currentNfeTerceiro = response.data
        this.syncCurrentToList()
        return response.data
      } catch (error) {
        console.error('Erro ao atualizar NFe de terceiro:', error)
        throw error
      } finally {
        this.loading.nfeTerceiro = false
      }
    },

    async manifestacao(codnfeterceiro, data) {
      this.loading.nfeTerceiro = true
      try {
        const response = await nfeTerceiroService.manifestacao(codnfeterceiro, data)
        // Recarrega para obter dados atualizados
        await this.fetchNfeTerceiro(codnfeterceiro)
        return response
      } catch (error) {
        console.error('Erro ao manifestar NFe:', error)
        throw error
      } finally {
        this.loading.nfeTerceiro = false
      }
    },

    async downloadNfe(codnfeterceiro) {
      this.loading.nfeTerceiro = true
      try {
        const response = await nfeTerceiroService.download(codnfeterceiro)
        this.currentNfeTerceiro = response.data
        this.syncCurrentToList()
        return response.data
      } catch (error) {
        console.error('Erro ao fazer download da NFe:', error)
        throw error
      } finally {
        this.loading.nfeTerceiro = false
      }
    },

    async toggleRevisao(codnfeterceiro) {
      try {
        const response = await nfeTerceiroService.revisao(codnfeterceiro)
        if (this.currentNfeTerceiro?.codnfeterceiro === codnfeterceiro) {
          this.currentNfeTerceiro = { ...this.currentNfeTerceiro, ...response.data }
        }
        this.syncCurrentToList()
        return response.data
      } catch (error) {
        console.error('Erro ao alterar revisão:', error)
        throw error
      }
    },

    async toggleConferencia(codnfeterceiro) {
      try {
        const response = await nfeTerceiroService.conferencia(codnfeterceiro)
        if (this.currentNfeTerceiro?.codnfeterceiro === codnfeterceiro) {
          this.currentNfeTerceiro = { ...this.currentNfeTerceiro, ...response.data }
        }
        this.syncCurrentToList()
        return response.data
      } catch (error) {
        console.error('Erro ao alterar conferência:', error)
        throw error
      }
    },

    // ==================== IMPORTAÇÃO ====================

    async validarImportacao(codnfeterceiro) {
      try {
        return await nfeTerceiroService.validarImportacao(codnfeterceiro)
      } catch (error) {
        console.error('Erro ao validar importação:', error)
        throw error
      }
    },

    async importar(codnfeterceiro) {
      this.loading.nfeTerceiro = true
      try {
        const response = await nfeTerceiroService.importar(codnfeterceiro)
        this.currentNfeTerceiro = { ...this.currentNfeTerceiro, ...response.data }
        this.syncCurrentToList()
        return response.data
      } catch (error) {
        console.error('Erro ao importar NFe:', error)
        throw error
      } finally {
        this.loading.nfeTerceiro = false
      }
    },

    // ==================== OPERAÇÕES SOBRE ITENS ====================

    async buscarItem(codnfeterceiro, barras) {
      try {
        const response = await nfeTerceiroService.buscarItem(codnfeterceiro, barras)
        return response.items
      } catch (error) {
        console.error('Erro ao buscar item:', error)
        throw error
      }
    },

    async updateItem(codnfeterceiro, codnfeterceiroitem, data) {
      try {
        const response = await nfeTerceiroService.updateItem(codnfeterceiro, codnfeterceiroitem, data)
        this.currentNfeTerceiro = response.data
        this.syncCurrentToList()
        return response.data
      } catch (error) {
        console.error('Erro ao atualizar item:', error)
        throw error
      }
    },

    async toggleConferenciaItem(codnfeterceiro, codnfeterceiroitem) {
      try {
        const response = await nfeTerceiroService.conferenciaItem(codnfeterceiro, codnfeterceiroitem)
        this.currentNfeTerceiro = response.data
        this.syncCurrentToList()
        return response.data
      } catch (error) {
        console.error('Erro ao alterar conferência do item:', error)
        throw error
      }
    },

    async dividirItem(codnfeterceiro, codnfeterceiroitem, parcelas) {
      try {
        const response = await nfeTerceiroService.dividirItem(codnfeterceiro, codnfeterceiroitem, parcelas)
        this.currentNfeTerceiro = response.data
        this.syncCurrentToList()
        return response.data
      } catch (error) {
        console.error('Erro ao dividir item:', error)
        throw error
      }
    },

    async marcarTipoProduto(codnfeterceiro, codtipoproduto) {
      try {
        const response = await nfeTerceiroService.marcarTipoProduto(codnfeterceiro, codtipoproduto)
        this.currentNfeTerceiro = response.data
        this.syncCurrentToList()
        return response.data
      } catch (error) {
        console.error('Erro ao marcar tipo de produto:', error)
        throw error
      }
    },

    async conferirTodos(codnfeterceiro) {
      try {
        const response = await nfeTerceiroService.conferirTodos(codnfeterceiro)
        this.currentNfeTerceiro = response.data
        this.syncCurrentToList()
        return response.data
      } catch (error) {
        console.error('Erro ao conferir todos:', error)
        throw error
      }
    },

    async informarComplemento(codnfeterceiro, valor) {
      try {
        const response = await nfeTerceiroService.informarComplemento(codnfeterceiro, valor)
        this.currentNfeTerceiro = response.data
        this.syncCurrentToList()
        return response.data
      } catch (error) {
        console.error('Erro ao informar complemento:', error)
        throw error
      }
    },

    // Força recarregamento (ignora cache)
    async fetchNfeTerceiroForce(codnfeterceiro) {
      this.currentNfeTerceiro = null
      return this.fetchNfeTerceiro(codnfeterceiro)
    },

    setFilters(filters) {
      this.filters = { ...this.filters, ...filters }
      this.initialLoadDone = false
    },

    clearFilters() {
      this.filters = {
        codfilial: null,
        codpessoa: null,
        codgrupoeconomico: null,
        codnaturezaoperacao: null,
        nfechave: null,
        emissao_inicio: null,
        emissao_fim: null,
        indsituacao: null,
        indmanifestacao: null,
        ignorada: null,
        revisao: null,
        conferencia: null,
        importacao: null,
      }
      this.initialLoadDone = false
    },

    clearCurrentNfeTerceiro() {
      this.currentNfeTerceiro = null
    },

    syncCurrentToList() {
      if (!this.currentNfeTerceiro) return

      const index = this.items.findIndex(
        (n) => n.codnfeterceiro === this.currentNfeTerceiro.codnfeterceiro,
      )
      if (index !== -1) {
        this.items[index] = { ...this.currentNfeTerceiro }
      }
    },
  },
})

import { defineStore } from 'pinia'
import { api } from 'src/services/api'

export const useSelectTributacaoStore = defineStore('selectTributacao', {
  state: () => ({
    tributacoes: [],
    tributacoesLoaded: false,
  }),

  getters: {},

  actions: {
    /**
     * Carrega todas as tributações (apenas uma vez)
     */
    async loadAll(force = false) {
      if (this.tributacoesLoaded && !force) {
        return this.tributacoes
      }

      try {
        const response = await api.get('v1/select/tributacao')
        this.tributacoes = response.data.map((item) => ({
          label: item.tributacao,
          value: item.codtributacao,
          tributacao: item.tributacao,
        }))
        this.tributacoesLoaded = true
        return this.tributacoes
      } catch (error) {
        console.error('Erro ao carregar tributações:', error)
        throw error
      }
    },

    /**
     * Busca tributação por texto (filtra localmente)
     */
    async search(busca) {
      if (!this.tributacoesLoaded) {
        await this.loadAll()
      }

      if (!busca || busca.length < 1) {
        return this.tributacoes
      }

      const needle = busca.toLowerCase()
      return this.tributacoes.filter(
        (t) => t.tributacao?.toLowerCase().indexOf(needle) > -1,
      )
    },

    /**
     * Busca uma tributação específica por código
     */
    async fetch(codtributacao) {
      if (!this.tributacoesLoaded) {
        await this.loadAll()
      }

      return this.tributacoes.find((t) => t.value === codtributacao) || null
    },

    /**
     * Limpa o cache
     */
    clear() {
      this.tributacoes = []
      this.tributacoesLoaded = false
    },
  },
})

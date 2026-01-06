import { defineStore } from 'pinia'
import { api } from 'src/services/api'

export const useSelectTributoStore = defineStore('selectTributo', {
  state: () => ({
    // Cache de todos os tributos
    tributos: [],
    tributosLoaded: false,
  }),

  getters: {},

  actions: {
    /**
     * Carrega todos os tributos (apenas uma vez)
     */
    async loadAll(force = false) {
      // Se já carregou e não é force, retorna o cache
      if (this.tributosLoaded && !force) {
        return this.tributos
      }

      try {
        const response = await api.get('v1/select/tributo')
        this.tributos = response.data.map((item) => ({
          label: `${item.codigo} - ${item.ente}`,
          value: item.codtributo,
          codigo: item.codigo,
          sigla: item.sigla,
          descricao: item.descricao,
          ente: item.ente,
        }))
        this.tributosLoaded = true
        return this.tributos
      } catch (error) {
        console.error('Erro ao carregar tributos:', error)
        throw error
      }
    },

    /**
     * Busca tributo por texto (filtra localmente)
     */
    async search(busca) {
      // Garante que os tributos estão carregados
      if (!this.tributosLoaded) {
        await this.loadAll()
      }

      if (!busca || busca.length < 1) {
        return []
      }

      // Filtra localmente
      const needle = busca.toLowerCase()
      return this.tributos.filter(
        (t) =>
          t.codigo?.toLowerCase().indexOf(needle) > -1 ||
          t.sigla?.toLowerCase().indexOf(needle) > -1 ||
          t.ente?.toLowerCase().indexOf(needle) > -1 ||
          t.descricao?.toLowerCase().indexOf(needle) > -1,
      )
    },

    /**
     * Busca um tributo específico por código
     */
    async fetch(codtributo) {
      // Garante que os tributos estão carregados
      if (!this.tributosLoaded) {
        await this.loadAll()
      }

      // Busca no cache local
      return this.tributos.find((t) => t.value === codtributo) || null
    },

    /**
     * Limpa o cache de tributos
     */
    clear() {
      this.tributos = []
      this.tributosLoaded = false
    },
  },
})

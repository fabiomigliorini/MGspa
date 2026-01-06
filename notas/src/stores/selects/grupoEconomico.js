import { defineStore } from 'pinia'
import { api } from 'src/services/api'

export const useSelectGrupoEconomicoStore = defineStore('selectGrupoEconomico', {
  state: () => ({
    // Cache de grupo econômico por busca
    grupoEconomicoCache: {},
  }),

  getters: {},

  actions: {
    /**
     * Busca grupo econômico por texto
     */
    async search(busca) {
      if (!busca || busca.length < 2) {
        return []
      }

      // Verifica cache
      const cacheKey = busca.toLowerCase()
      if (this.grupoEconomicoCache[cacheKey]) {
        return this.grupoEconomicoCache[cacheKey]
      }

      try {
        const response = await api.get('v1/select/grupo-economico', {
          params: { busca },
        })
        const grupos = response.data.map((item) => ({
          label: item.grupoeconomico,
          value: item.codgrupoeconomico,
        }))

        // Salva no cache
        this.grupoEconomicoCache[cacheKey] = grupos
        return grupos
      } catch (error) {
        console.error('Erro ao buscar grupo econômico:', error)
        throw error
      }
    },

    /**
     * Busca um grupo econômico específico por código
     */
    async fetch(codgrupoeconomico) {
      try {
        const response = await api.get('v1/select/grupo-economico', {
          params: { codgrupoeconomico },
        })
        if (response.data.length > 0) {
          const item = response.data[0]
          return {
            label: item.grupoeconomico,
            value: item.codgrupoeconomico,
          }
        }
        return null
      } catch (error) {
        console.error('Erro ao buscar grupo econômico:', error)
        throw error
      }
    },

    /**
     * Limpa o cache de grupo econômico
     */
    clear() {
      this.grupoEconomicoCache = {}
    },
  },
})

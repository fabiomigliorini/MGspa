import { defineStore } from 'pinia'
import { api } from 'src/boot/axios'

export const useSelectCidadeStore = defineStore('selectCidade', {
  state: () => ({
    // Cache de cidades por busca
    cidadesCache: {},
  }),

  getters: {},

  actions: {
    /**
     * Busca cidades por texto
     */
    async search(busca) {
      if (!busca || busca.length < 2) {
        return []
      }

      // Verifica cache
      const cacheKey = busca.toLowerCase()
      if (this.cidadesCache[cacheKey]) {
        return this.cidadesCache[cacheKey]
      }

      try {
        const response = await api.get('/select/cidade', {
          params: { cidade: busca },
        })
        const cidades = response.data.map((item) => ({
          label: item.label,
          value: item.value,
        }))

        // Salva no cache
        this.cidadesCache[cacheKey] = cidades
        return cidades
      } catch (error) {
        console.error('Erro ao buscar cidades:', error)
        throw error
      }
    },

    /**
     * Busca uma cidade específica por código
     */
    async fetch(codcidade) {
      try {
        const response = await api.get('/select/cidade', {
          params: { codcidade },
        })
        if (response.data.length > 0) {
          return {
            label: response.data[0].label,
            value: response.data[0].value,
          }
        }
        return null
      } catch (error) {
        console.error('Erro ao buscar cidade:', error)
        throw error
      }
    },

    /**
     * Limpa o cache
     */
    clear() {
      this.cidadesCache = {}
    },
  },
})

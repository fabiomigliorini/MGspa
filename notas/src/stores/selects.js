import { defineStore } from 'pinia'
import { api } from 'src/boot/axios'

export const useSelectsStore = defineStore('selects', {
  state: () => ({
    // Cache de estados
    estados: [],
    estadosLoaded: false,

    // Cache de cidades por busca
    cidadesCache: {},
  }),

  actions: {
    /**
     * Busca todos os estados
     */
    async fetchEstados(force = false) {
      // Se já carregou e não é force, retorna o cache
      if (this.estadosLoaded && !force) {
        return this.estados
      }

      try {
        const response = await api.get('/select/estado')
        this.estados = response.data.map((item) => ({
          label: item.sigla,
          value: item.value,
          sigla: item.sigla,
          nome: item.label,
        }))
        this.estadosLoaded = true
        return this.estados
      } catch (error) {
        console.error('Erro ao buscar estados:', error)
        throw error
      }
    },

    /**
     * Busca um estado específico por código
     */
    async fetchEstado(codestado) {
      // Verifica se já tem no cache
      if (this.estadosLoaded) {
        const estado = this.estados.find((e) => e.value === codestado)
        if (estado) {
          return estado
        }
      }

      // Se não tem no cache, carrega todos
      await this.fetchEstados()
      return this.estados.find((e) => e.value === codestado)
    },

    /**
     * Busca cidades por texto
     */
    async searchCidades(busca) {
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
    async fetchCidade(codcidade) {
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
     * Limpa o cache de cidades
     */
    clearCidadesCache() {
      this.cidadesCache = {}
    },
  },
})

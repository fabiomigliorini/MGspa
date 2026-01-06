import { defineStore } from 'pinia'
import { api } from 'src/services/api'

export const useSelectEstadoStore = defineStore('selectEstado', {
  state: () => ({
    // Cache de estados
    estados: [],
    estadosLoaded: false,
  }),

  getters: {},

  actions: {
    /**
     * Busca todos os estados
     */
    async search(force = false) {
      // Se já carregou e não é force, retorna o cache
      if (this.estadosLoaded && !force) {
        return this.estados
      }

      try {
        const response = await api.get('v1/select/estado')
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
    async fetch(codestado) {
      // Verifica se já tem no cache
      if (this.estadosLoaded) {
        const estado = this.estados.find((e) => e.value === codestado)
        if (estado) {
          return estado
        }
      }

      // Se não tem no cache, carrega todos
      await this.search()
      return this.estados.find((e) => e.value === codestado)
    },
  },
})

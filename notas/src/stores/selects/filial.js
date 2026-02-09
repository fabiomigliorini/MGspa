import { defineStore } from 'pinia'
import { api } from 'src/services/api'

export const useSelectFilialStore = defineStore('selectFilial', {
  state: () => ({
    // Lista completa de filiais (carregada uma vez)
    filiais: [],
    // Flag para controlar se já foi carregado
    loaded: false,
  }),

  getters: {},

  actions: {
    /**
     * Carrega todas as filiais (apenas uma vez)
     */
    async loadAll() {
      // Se já carregou, não faz nada
      if (this.loaded) {
        return this.filiais
      }

      try {
        const response = await api.get('v1/select/filial')

        // Mapeia e ordena por value
        this.filiais = response.data
          .map((item) => ({
            label: item.label,
            value: item.value,
            nfeserie: item.nfeserie,
          }))
          .sort((a, b) => a.value - b.value)

        this.loaded = true
        return this.filiais
      } catch (error) {
        console.error('Erro ao carregar filiais:', error)
        throw error
      }
    },

    /**
     * Filtra filiais localmente por texto
     */
    filter(busca) {
      if (!busca) {
        return this.filiais
      }

      const buscaLower = busca.toLowerCase()
      return this.filiais.filter((filial) => {
        return (
          filial.label.toLowerCase().includes(buscaLower) ||
          filial.value.toString().includes(buscaLower)
        )
      })
    },

    /**
     * Busca uma filial específica por código
     */
    getByCode(codfilial) {
      return this.filiais.find((f) => f.value === codfilial) || null
    },

    /**
     * Limpa o cache de filial
     */
    clear() {
      this.filiais = []
      this.loaded = false
    },
  },
})

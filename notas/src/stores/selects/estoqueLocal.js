import { defineStore } from 'pinia'
import { api } from 'src/boot/axios'

export const useSelectEstoqueLocalStore = defineStore('selectEstoqueLocal', {
  state: () => ({
    // Lista completa de estoques locais (carregada uma vez)
    locais: [],
    // Flag para controlar se já foi carregado
    loaded: false,
  }),

  getters: {},

  actions: {
    /**
     * Carrega todos os estoques locais (apenas uma vez)
     */
    async loadAll() {
      // Se já carregou, não faz nada
      if (this.loaded) {
        return this.locais
      }

      try {
        const response = await api.get('v1/select/estoque-local')

        // Mapeia e ordena por value
        this.locais = response.data
          .map((item) => ({
            label: item.label,
            value: item.value,
          }))
          .sort((a, b) => a.value - b.value)

        this.loaded = true
        return this.locais
      } catch (error) {
        console.error('Erro ao carregar estoques locais:', error)
        throw error
      }
    },

    /**
     * Filtra estoques locais localmente por texto
     */
    filter(busca) {
      if (!busca) {
        return this.locais
      }

      const buscaLower = busca.toLowerCase()
      return this.locais.filter((local) => {
        return (
          local.label.toLowerCase().includes(buscaLower) ||
          local.value.toString().includes(buscaLower)
        )
      })
    },

    /**
     * Busca um estoque local específico por código
     */
    getByCode(codestoquelocal) {
      return this.locais.find((l) => l.value === codestoquelocal) || null
    },

    /**
     * Limpa o cache de estoque local
     */
    clear() {
      this.locais = []
      this.loaded = false
    },
  },
})

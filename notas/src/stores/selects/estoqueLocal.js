import { defineStore } from 'pinia'
import { api } from 'src/services/api'

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

        // Mapeia e ordena por value, mantendo o codfilial
        this.locais = response.data
          .map((item) => ({
            label: item.label,
            value: item.value,
            codfilial: item.codfilial,
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
     * Filtra estoques locais por filial
     */
    filterByFilial(codfilial) {
      if (!codfilial) {
        return this.locais
      }

      return this.locais.filter((local) => local.codfilial === codfilial)
    },

    /**
     * Filtra estoques locais por filial e texto
     */
    filterByFilialAndText(codfilial, busca) {
      let resultado = this.locais

      // Filtra por filial se fornecido
      if (codfilial) {
        resultado = resultado.filter((local) => local.codfilial === codfilial)
      }

      // Filtra por texto se fornecido
      if (busca) {
        const buscaLower = busca.toLowerCase()
        resultado = resultado.filter((local) => {
          return (
            local.label.toLowerCase().includes(buscaLower) ||
            local.value.toString().includes(buscaLower)
          )
        })
      }

      return resultado
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

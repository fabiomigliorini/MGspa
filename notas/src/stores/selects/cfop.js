import { defineStore } from 'pinia'
import { api } from 'src/services/api'

export const useSelectCfopStore = defineStore('selectCfop', {
  state: () => ({
    cfops: [],
    cfopsLoaded: false,
  }),

  getters: {},

  actions: {
    /**
     * Carrega todos os CFOPs (apenas uma vez)
     */
    async loadAll(force = false) {
      if (this.cfopsLoaded && !force) {
        return this.cfops
      }

      try {
        const response = await api.get('v1/cfop', { params: { per_page: 1000 } })
        this.cfops = (response.data?.data || []).map((item) => ({
          label: `${item.codcfop} - ${item.descricao}`,
          value: item.codcfop,
          codcfop: item.codcfop,
          cfop: item.cfop,
          descricao: item.descricao,
        }))
        this.cfopsLoaded = true
        return this.cfops
      } catch (error) {
        console.error('Erro ao carregar CFOPs:', error)
        throw error
      }
    },

    /**
     * Busca CFOP por texto (filtra localmente)
     */
    async search(busca) {
      if (!this.cfopsLoaded) {
        await this.loadAll()
      }

      if (!busca || busca.length < 1) {
        return this.cfops
      }

      const needle = busca.toLowerCase()
      return this.cfops.filter(
        (c) =>
          String(c.codcfop)?.toLowerCase().indexOf(needle) > -1 ||
          c.cfop?.toLowerCase().indexOf(needle) > -1 ||
          c.descricao?.toLowerCase().indexOf(needle) > -1,
      )
    },

    /**
     * Busca um CFOP específico por código
     */
    async fetch(codcfop) {
      if (!this.cfopsLoaded) {
        await this.loadAll()
      }

      return this.cfops.find((c) => c.value === codcfop) || null
    },

    /**
     * Limpa o cache
     */
    clear() {
      this.cfops = []
      this.cfopsLoaded = false
    },
  },
})

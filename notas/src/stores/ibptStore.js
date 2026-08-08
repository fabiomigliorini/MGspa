import { defineStore } from 'pinia'
import ibptService from '../services/ibptService'

export const useIbptStore = defineStore('ibpt', {
  state: () => ({
    estados: [],
    loaded: false,
    loading: false,
  }),

  getters: {
    // A tabela do IBPT vale para todas as UFs ao mesmo tempo, entao o que interessa
    // e a pior situacao entre elas: e ela que trava a informacao da Lei 12.741.
    vigenciafim: (state) => {
      const datas = state.estados.map((e) => e.vigenciafim).filter(Boolean)
      return datas.length ? datas.sort()[0] : null
    },

    diasparavencer: (state) => {
      const dias = state.estados.map((e) => e.diasparavencer).filter((d) => d !== null)
      return dias.length ? Math.min(...dias) : null
    },

    versao: (state) => state.estados.map((e) => e.versao).find(Boolean) || null,

    carregadas: (state) => state.estados.filter((e) => e.ncms > 0).length,
  },

  actions: {
    async fetchStatus(force = false) {
      if (this.loaded && !force) return
      this.loading = true
      try {
        this.estados = await ibptService.status()
        this.loaded = true
      } finally {
        this.loading = false
      }
    },

    async importar(uf, arquivo) {
      return await ibptService.importar(uf, arquivo)
    },
  },
})

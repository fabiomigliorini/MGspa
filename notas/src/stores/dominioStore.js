import { defineStore } from 'pinia'
import dominioService from '../services/dominioService'

export const useDominioStore = defineStore('dominio', {
  state: () => ({
    empresas: [],
    loaded: false,
    loading: false,
  }),

  actions: {
    async fetchEmpresas(force = false) {
      if (this.loaded && !force) return
      this.loading = true
      try {
        this.empresas = await dominioService.empresas()
        this.loaded = true
      } finally {
        this.loading = false
      }
    },

    // Localiza a filial pelo código dentro da árvore de empresas.
    findFilial(codfilial) {
      for (const empresa of this.empresas) {
        const filial = empresa.filiais.find((f) => f.codfilial === codfilial)
        if (filial) return filial
      }
      return null
    },

    upsertAcumulador(acum) {
      const filial = this.findFilial(acum.codfilial)
      if (!filial) return
      const idx = filial.acumuladores.findIndex(
        (a) => a.codcfop === acum.codcfop && a.icmscst === acum.icmscst,
      )
      if (idx === -1) filial.acumuladores.push(acum)
      else filial.acumuladores[idx] = acum
    },

    removeAcumulador(acum) {
      const filial = this.findFilial(acum.codfilial)
      if (!filial) return
      filial.acumuladores = filial.acumuladores.filter(
        (a) => a.coddominioacumulador !== acum.coddominioacumulador,
      )
    },
  },
})

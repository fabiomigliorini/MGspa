import { defineStore } from 'pinia'
import { api } from 'src/services/api'

export const empresaStore = defineStore('empresa', {
  persist: {
    paths: ['empresas', 'filtroPesquisa'],
  },

  state: () => ({
    item: {},
    empresas: [],
    filtroPesquisa: {
      empresa: '',
      codempresa: '',
      page: 1,
      per_page: 20,
    },
    filiais: [],
    filial: {},
    // Percentuais do Simples por mes da filial aberta (TASK-280)
    filialSimples: [],
    filtroFilial: {
      filial: '',
    },
    loadingFiliais: false,
  }),

  actions: {
    async buscarFiliais(codempresa) {
      this.loadingFiliais = true
      try {
        const ret = await api.get('v1/filial', {
          params: {
            codempresa,
            filial: this.filtroFilial.filial || undefined,
          },
        })
        this.filiais = ret.data.data
        return ret
      } finally {
        this.loadingFiliais = false
      }
    },

    async getFilial(codfilial) {
      const ret = await api.get('v1/filial/' + codfilial)
      this.filial = ret.data.data || {}
      return ret
    },

    async buscarEmpresas() {
      const ret = await api.get('v1/empresa', {
        params: this.filtroPesquisa,
      })
      if (this.filtroPesquisa.page === 1) {
        this.empresas = ret.data.data
      } else {
        this.empresas = [...this.empresas, ...ret.data.data]
      }
      return ret
    },

    async get(codempresa) {
      const ret = await api.get('v1/empresa/' + codempresa)
      this.item = ret.data.data
      return ret
    },

    async criarEmpresa(model) {
      const ret = await api.post('v1/empresa', model)
      return ret
    },

    async atualizarEmpresa(codempresa, model) {
      const ret = await api.put('v1/empresa/' + codempresa, model)
      this.item = ret.data.data
      return ret
    },

    async criarFilial(model) {
      const ret = await api.post('v1/filial', model)
      return ret
    },

    /**
     * Forca a entrada em contingencia off-line da NFC-e.
     * POST entra / DELETE sai — nunca toggle.
     */
    async entrarContingencia(codempresa, justificativa) {
      const ret = await api.post(`v1/empresa/${codempresa}/contingencia`, { justificativa })
      this.item = ret.data.data
      return this.item
    },

    async sairContingencia(codempresa) {
      const ret = await api.delete(`v1/empresa/${codempresa}/contingencia`)
      this.item = ret.data.data
      return this.item
    },

    async atualizarFilial(codfilial, model) {
      const ret = await api.put('v1/filial/' + codfilial, model)
      this.filial = ret.data.data
      return ret
    },

    async removerFilial(codfilial) {
      const ret = await api.delete('v1/filial/' + codfilial)
      this.filiais = this.filiais.filter((f) => f.codfilial !== codfilial)
      return ret
    },

    // Percentuais do Simples por mes da filial, para a nota de servico (TASK-280)
    async simplesListar(codfilial) {
      const ret = await api.get('v1/filial/' + codfilial + '/simples')
      this.filialSimples = ret.data.data
      return this.filialSimples
    },

    async simplesCriar(codfilial, model) {
      await api.post('v1/filial/' + codfilial + '/simples', model)
      return this.simplesListar(codfilial)
    },

    async simplesAtualizar(codfilial, codfilialsimples, model) {
      await api.put('v1/filial/' + codfilial + '/simples/' + codfilialsimples, model)
      return this.simplesListar(codfilial)
    },

    async simplesExcluir(codfilial, codfilialsimples) {
      await api.delete('v1/filial/' + codfilial + '/simples/' + codfilialsimples)
      return this.simplesListar(codfilial)
    },

    async removerEmpresa(codempresa) {
      const ret = await api.delete('v1/empresa/' + codempresa)
      this.empresas = this.empresas.filter((e) => e.codempresa !== codempresa)
      return ret
    },

    limparFiltro() {
      this.filtroPesquisa = {
        empresa: '',
        codempresa: '',
        page: 1,
        per_page: 20,
      }
    },
  },
})

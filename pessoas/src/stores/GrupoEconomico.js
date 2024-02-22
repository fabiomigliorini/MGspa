import { defineStore } from 'pinia';
import { api } from 'src/boot/axios'

export const GrupoEconomicoStore = defineStore('grupoeconomico', {
  persist: true,

  state: () => ({
    filtroGrupoPesquisa: {
      nome: null,
      page: 1,
    },
    arrGrupos:[]
  }),

  actions: {
    async selectGrupoEconomico(grupoeconomico) {
      const ret = await api.get('v1/grupoeconomico/select?grupoeconomico=' + grupoeconomico);
      return ret;
    },

    async selectGrupoEconomicoPeloCodigo(codgrupoeconomico) {
      const ret = await api.get('v1/grupoeconomico/select?codgrupoeconomico=' + codgrupoeconomico);
      return ret;
    },

    async novoGrupoEconomico(grupoeconomico) {
      grupoeconomico = { grupoeconomico: grupoeconomico }
      const ret = await api.post('v1/grupoeconomico', grupoeconomico)
      return ret;
    },

    async getGrupoEconomico(codgrupoeconomico) {
      const ret = await api.get('v1/grupoeconomico/' + codgrupoeconomico);
      return ret;
    },

    async buscaGrupos() {
      const ret = await api.get('v1/grupoeconomico/', {params: this.filtroGrupoPesquisa});
      if (this.filtroGrupoPesquisa.page == 1) {
        this.arrGrupos = ret.data.data;
      } else {
        this.arrGrupos.push(...ret.data.data);
      }
      return ret;
    },

    async removerdoGrupo(codpessoa, codgrupoeconomico) {
      const ret = await api.delete('v1/pessoa/' + codpessoa + '/grupoeconomico/' + codgrupoeconomico + '/removerdogrupo');
      return ret;
    },

    async salvarGrupoEconomico(codgrupoeconomico, modelGrupoEconomico) {
      const ret = await api.put('v1/grupoeconomico/' + codgrupoeconomico, modelGrupoEconomico);
      return ret;
    },

    async excluirGrupoEconomico(codgrupoeconomico) {
      const ret = await api.delete('v1/grupoeconomico/' + codgrupoeconomico);
      return ret;
    },

    async inativarGrupo(codgrupoeconomico) {
      const ret = await api.post('v1/grupoeconomico/' + codgrupoeconomico + '/inativo');
      return ret;
    },


    async ativarGrupo(codgrupoeconomico) {
      const ret = await api.delete('v1/grupoeconomico/' + codgrupoeconomico + '/inativo');
      return ret;
    },

    async getNegocios(codgrupoeconomico, codpessoa) {
      const ret = await api.get('v1/grupo-economico/' + codgrupoeconomico + '/negocios', {params: codpessoa});
      return ret;
    },

    async getTopProdutos(codgrupoeconomico, codpessoa) {
      const ret = await api.get('v1/grupo-economico/' + codgrupoeconomico + '/top-produtos', {params: codpessoa});
      return ret;
    },

  }
})

import { defineStore } from "pinia";
import { api } from "boot/axios";

export const metaStore = defineStore("meta", {
  state: () => ({
    listagem: [],
    item: {},
    dashboard: {},
    dashboardColaborador: {},
  }),

  actions: {
    async getListagem() {
      const ret = await api.get("v1/meta");
      this.listagem = ret.data.data;
      return ret;
    },

    async get(codmeta) {
      const ret = await api.get("v1/meta/" + codmeta);
      this.item = ret.data.data;
      return ret;
    },

    async criar(model) {
      const ret = await api.post("v1/meta", model);
      return ret;
    },

    async atualizar(codmeta, model) {
      const ret = await api.put("v1/meta/" + codmeta, model);
      return ret;
    },

    async excluir(codmeta) {
      const ret = await api.delete("v1/meta/" + codmeta);
      this.listagem = this.listagem.filter((m) => m.codmeta !== codmeta);
      return ret;
    },

    async reprocessar(codmeta) {
      const ret = await api.post("v1/meta/" + codmeta + "/reprocessar");
      return ret;
    },

    async bloquear(codmeta) {
      const ret = await api.post("v1/meta/" + codmeta + "/bloquear");
      return ret;
    },

    async desbloquear(codmeta) {
      const ret = await api.post("v1/meta/" + codmeta + "/desbloquear");
      return ret;
    },

    async finalizar(codmeta) {
      const ret = await api.post("v1/meta/" + codmeta + "/finalizar");
      return ret;
    },

    async getDashboard(codmeta) {
      const ret = await api.get("v1/meta/" + codmeta + "/dashboard");
      this.dashboard = ret.data.data;
      return ret;
    },

    async getDashboardColaborador(codmeta, codpessoa) {
      const ret = await api.get(
        "v1/meta/" + codmeta + "/dashboard/" + codpessoa
      );
      this.dashboardColaborador = ret.data.data;
      return ret;
    },

    // --- UNIDADE (individual) ---

    async criarUnidade(codmeta, data) {
      const ret = await api.post("v1/meta/" + codmeta + "/unidade", data);
      this.item = ret.data.data;
      return ret;
    },

    async atualizarUnidade(codmeta, codunidadenegocio, data) {
      const ret = await api.put(
        "v1/meta/" + codmeta + "/unidade/" + codunidadenegocio,
        data
      );
      this.item = ret.data.data;
      return ret;
    },

    async removerUnidade(codmeta, codunidadenegocio) {
      const ret = await api.delete(
        "v1/meta/" + codmeta + "/unidade/" + codunidadenegocio
      );
      this.item = ret.data.data;
      return ret;
    },

    // --- PESSOA (individual) ---

    async criarPessoa(codmeta, codunidadenegocio, data) {
      const ret = await api.post(
        "v1/meta/" + codmeta + "/unidade/" + codunidadenegocio + "/pessoa",
        data
      );
      this.item = ret.data.data;
      return ret;
    },

    async atualizarPessoa(codmeta, id, data) {
      const ret = await api.put(
        "v1/meta/" + codmeta + "/pessoa/" + id,
        data
      );
      this.item = ret.data.data;
      return ret;
    },

    async removerPessoa(codmeta, id) {
      const ret = await api.delete("v1/meta/" + codmeta + "/pessoa/" + id);
      this.item = ret.data.data;
      return ret;
    },

    // --- FIXO (individual) ---

    async criarFixo(codmeta, idPessoa, data) {
      const ret = await api.post(
        "v1/meta/" + codmeta + "/pessoa/" + idPessoa + "/fixo",
        data
      );
      this.item = ret.data.data;
      return ret;
    },

    async atualizarFixo(codmeta, id, data) {
      const ret = await api.put("v1/meta/" + codmeta + "/fixo/" + id, data);
      this.item = ret.data.data;
      return ret;
    },

    async removerFixo(codmeta, id) {
      const ret = await api.delete("v1/meta/" + codmeta + "/fixo/" + id);
      this.item = ret.data.data;
      return ret;
    },
  },
});

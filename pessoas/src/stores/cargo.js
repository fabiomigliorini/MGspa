import { defineStore } from "pinia";
import { api } from "boot/axios";

export const cargoStore = defineStore("cargo", {
  state: () => ({
    listagem: [],
    item: {},
  }),

  actions: {
    async getListagem() {
      const ret = await api.get("v1/cargo");
      this.listagem = ret.data.data;
      return ret;
    },

    async get(codcargo) {
      const ret = await api.get("v1/cargo/" + codcargo);
      this.item = ret.data.data;
      return ret;
    },

    async criar(model) {
      const ret = await api.post("v1/cargo", model);
      this.listagem.push(ret.data.data);
      return ret;
    },

    async atualizar(codcargo, model) {
      const ret = await api.put("v1/cargo/" + codcargo, model);
      const i = this.listagem.findIndex((c) => c.codcargo === codcargo);
      if (i !== -1) this.listagem[i] = ret.data.data;
      return ret;
    },

    async excluir(codcargo) {
      const ret = await api.delete("v1/cargo/" + codcargo);
      this.listagem = this.listagem.filter((c) => c.codcargo !== codcargo);
      return ret;
    },

    async inativar(codcargo) {
      const ret = await api.post("v1/cargo/" + codcargo + "/inativo");
      const i = this.listagem.findIndex((c) => c.codcargo === codcargo);
      if (i !== -1) this.listagem[i] = ret.data.data;
      return ret;
    },

    async ativar(codcargo) {
      const ret = await api.delete("v1/cargo/" + codcargo + "/inativo");
      const i = this.listagem.findIndex((c) => c.codcargo === codcargo);
      if (i !== -1) this.listagem[i] = ret.data.data;
      return ret;
    },
  },
});

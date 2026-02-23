import { defineStore } from "pinia";
import { api } from "boot/axios";

export const setorStore = defineStore("setor", {
  state: () => ({
    listagem: [],
  }),

  actions: {
    async getListagem() {
      const ret = await api.get("v1/setor");
      this.listagem = ret.data.data;
      return ret;
    },

    async criar(model) {
      const ret = await api.post("v1/setor", model);
      return ret;
    },

    async atualizar(codsetor, model) {
      const ret = await api.put("v1/setor/" + codsetor, model);
      return ret;
    },

    async excluir(codsetor) {
      const ret = await api.delete("v1/setor/" + codsetor);
      this.listagem = this.listagem.filter((s) => s.codsetor !== codsetor);
      return ret;
    },

    async inativar(codsetor) {
      const ret = await api.post("v1/setor/" + codsetor + "/inativo");
      const i = this.listagem.findIndex((s) => s.codsetor === codsetor);
      if (i !== -1) this.listagem[i] = ret.data.data;
      return ret;
    },

    async ativar(codsetor) {
      const ret = await api.delete("v1/setor/" + codsetor + "/inativo");
      const i = this.listagem.findIndex((s) => s.codsetor === codsetor);
      if (i !== -1) this.listagem[i] = ret.data.data;
      return ret;
    },
  },
});

import { defineStore } from "pinia";
import { api } from "boot/axios";

export const dependenteStore = defineStore("dependente", {
  actions: {
    async criar(model) {
      const ret = await api.post("v1/dependente/", model);
      return ret;
    },

    async alterar(coddependente, model) {
      const ret = await api.put("v1/dependente/" + coddependente + "/", model);
      return ret;
    },

    async excluir(coddependente) {
      const ret = await api.delete("v1/dependente/" + coddependente + "/");
      return ret;
    },

    async inativar(coddependente) {
      const ret = await api.post("v1/dependente/" + coddependente + "/inativo");
      return ret;
    },

    async ativar(coddependente) {
      const ret = await api.delete("v1/dependente/" + coddependente + "/inativo");
      return ret;
    },
  },
});

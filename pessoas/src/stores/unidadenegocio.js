import { defineStore } from "pinia";
import { api } from "boot/axios";

export const unidadeNegocioStore = defineStore("unidadenegocio", {
  state: () => ({
    listagem: [],
    item: {},
  }),

  actions: {
    async getListagem() {
      const ret = await api.get("v1/unidade-negocio");
      this.listagem = ret.data.data;
      return ret;
    },

    async get(codunidadenegocio) {
      const ret = await api.get("v1/unidade-negocio/" + codunidadenegocio);
      this.item = ret.data.data;
      return ret;
    },

    async criar(model) {
      const ret = await api.post("v1/unidade-negocio", model);
      return ret;
    },

    async atualizar(codunidadenegocio, model) {
      const ret = await api.put(
        "v1/unidade-negocio/" + codunidadenegocio,
        model
      );
      return ret;
    },

    async excluir(codunidadenegocio) {
      const ret = await api.delete(
        "v1/unidade-negocio/" + codunidadenegocio
      );
      this.listagem = this.listagem.filter(
        (u) => u.codunidadenegocio !== codunidadenegocio
      );
      return ret;
    },

    async inativar(codunidadenegocio) {
      const ret = await api.post(
        "v1/unidade-negocio/" + codunidadenegocio + "/inativo"
      );
      const i = this.listagem.findIndex(
        (u) => u.codunidadenegocio === codunidadenegocio
      );
      if (i !== -1) this.listagem[i] = ret.data.data;
      return ret;
    },

    async ativar(codunidadenegocio) {
      const ret = await api.delete(
        "v1/unidade-negocio/" + codunidadenegocio + "/inativo"
      );
      const i = this.listagem.findIndex(
        (u) => u.codunidadenegocio === codunidadenegocio
      );
      if (i !== -1) this.listagem[i] = ret.data.data;
      return ret;
    },
  },
});

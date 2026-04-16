import { defineStore } from "pinia";
import { api } from "boot/axios";

export const grupoClienteStore = defineStore("grupoCliente", {
  state: () => ({
    grupos: [],
    filtro: {
      grupocliente: null,
      status: "ativos",
    },
  }),

  actions: {
    async index() {
      const ret = await api.get("v1/grupo-cliente", { params: this.filtro });
      this.grupos = ret.data.data;
      return ret;
    },

    async get(codgrupocliente) {
      const ret = await api.get("v1/grupo-cliente/" + codgrupocliente);
      return ret;
    },

    async store(model) {
      const ret = await api.post("v1/grupo-cliente", model);
      this.grupos.push(ret.data.data);
      return ret;
    },

    async update(codgrupocliente, model) {
      const ret = await api.put("v1/grupo-cliente/" + codgrupocliente, model);
      const index = this.grupos.findIndex(
        (g) => g.codgrupocliente === codgrupocliente
      );
      if (index !== -1) {
        this.grupos[index] = ret.data.data;
      }
      return ret;
    },

    async destroy(codgrupocliente) {
      const ret = await api.delete("v1/grupo-cliente/" + codgrupocliente);
      this.grupos = this.grupos.filter(
        (g) => g.codgrupocliente !== codgrupocliente
      );
      return ret;
    },

    async inativar(codgrupocliente) {
      const ret = await api.post(
        "v1/grupo-cliente/" + codgrupocliente + "/inativo"
      );
      const index = this.grupos.findIndex(
        (g) => g.codgrupocliente === codgrupocliente
      );
      if (index !== -1) {
        this.grupos[index] = ret.data.data;
      }
      return ret;
    },

    async ativar(codgrupocliente) {
      const ret = await api.delete(
        "v1/grupo-cliente/" + codgrupocliente + "/inativo"
      );
      const index = this.grupos.findIndex(
        (g) => g.codgrupocliente === codgrupocliente
      );
      if (index !== -1) {
        this.grupos[index] = ret.data.data;
      }
      return ret;
    },
  },
});

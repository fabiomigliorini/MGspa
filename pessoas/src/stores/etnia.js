import { defineStore } from "pinia";
import { api } from "boot/axios";

export const etniaStore = defineStore("etnia", {
  state: () => ({
    etnias: [],
    filtro: {
      etnia: null,
      inativo: false,
    },
  }),

  actions: {
    async index() {
      const ret = await api.get("v1/etnia", { params: this.filtro });
      this.etnias = ret.data.data;
      return ret;
    },

    async get(codetnia) {
      const ret = await api.get("v1/etnia/" + codetnia);
      return ret;
    },

    async store(model) {
      const ret = await api.post("v1/etnia", model);
      this.etnias.push(ret.data.data);
      return ret;
    },

    async update(codetnia, model) {
      const ret = await api.put("v1/etnia/" + codetnia, model);
      const index = this.etnias.findIndex((e) => e.codetnia === codetnia);
      if (index !== -1) {
        this.etnias[index] = ret.data.data;
      }
      return ret;
    },

    async destroy(codetnia) {
      const ret = await api.delete("v1/etnia/" + codetnia);
      this.etnias = this.etnias.filter((e) => e.codetnia !== codetnia);
      return ret;
    },

    async inativar(codetnia) {
      const ret = await api.post("v1/etnia/" + codetnia + "/inativo");
      const index = this.etnias.findIndex((e) => e.codetnia === codetnia);
      if (index !== -1) {
        this.etnias[index] = ret.data.data;
      }
      return ret;
    },

    async ativar(codetnia) {
      const ret = await api.delete("v1/etnia/" + codetnia + "/inativo");
      const index = this.etnias.findIndex((e) => e.codetnia === codetnia);
      if (index !== -1) {
        this.etnias[index] = ret.data.data;
      }
      return ret;
    },
  },
});

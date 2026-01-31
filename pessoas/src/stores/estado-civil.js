import { defineStore } from "pinia";
import { api } from "boot/axios";

export const estadoCivilStore = defineStore("estadoCivil", {
  state: () => ({
    estadosCivis: [],
    filtro: {
      estadocivil: null,
      inativo: false,
    },
  }),

  actions: {
    async index() {
      const ret = await api.get("v1/estado-civil", { params: this.filtro });
      this.estadosCivis = ret.data.data;
      return ret;
    },

    async get(codestadocivil) {
      const ret = await api.get("v1/estado-civil/" + codestadocivil);
      return ret;
    },

    async store(model) {
      const ret = await api.post("v1/estado-civil", model);
      this.estadosCivis.push(ret.data.data);
      return ret;
    },

    async update(codestadocivil, model) {
      const ret = await api.put("v1/estado-civil/" + codestadocivil, model);
      const index = this.estadosCivis.findIndex(
        (e) => e.codestadocivil === codestadocivil
      );
      if (index !== -1) {
        this.estadosCivis[index] = ret.data.data;
      }
      return ret;
    },

    async destroy(codestadocivil) {
      const ret = await api.delete("v1/estado-civil/" + codestadocivil);
      this.estadosCivis = this.estadosCivis.filter(
        (e) => e.codestadocivil !== codestadocivil
      );
      return ret;
    },

    async inativar(codestadocivil) {
      const ret = await api.post(
        "v1/estado-civil/" + codestadocivil + "/inativo"
      );
      const index = this.estadosCivis.findIndex(
        (e) => e.codestadocivil === codestadocivil
      );
      if (index !== -1) {
        this.estadosCivis[index] = ret.data.data;
      }
      return ret;
    },

    async ativar(codestadocivil) {
      const ret = await api.delete(
        "v1/estado-civil/" + codestadocivil + "/inativo"
      );
      const index = this.estadosCivis.findIndex(
        (e) => e.codestadocivil === codestadocivil
      );
      if (index !== -1) {
        this.estadosCivis[index] = ret.data.data;
      }
      return ret;
    },
  },
});

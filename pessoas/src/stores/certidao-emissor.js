import { defineStore } from "pinia";
import { api } from "boot/axios";

export const certidaoEmissorStore = defineStore("certidaoEmissor", {
  state: () => ({
    certidaoEmissores: [],
    filtro: {
      certidaoemissor: null,
      inativo: 1,
    },
  }),

  actions: {
    async index() {
      const ret = await api.get("v1/certidao-emissor", { params: this.filtro });
      this.certidaoEmissores = ret.data.data;
      return ret;
    },

    async get(codcertidaoemissor) {
      const ret = await api.get("v1/certidao-emissor/" + codcertidaoemissor);
      return ret;
    },

    async store(model) {
      const ret = await api.post("v1/certidao-emissor", model);
      this.certidaoEmissores.push(ret.data.data);
      return ret;
    },

    async update(codcertidaoemissor, model) {
      const ret = await api.put("v1/certidao-emissor/" + codcertidaoemissor, model);
      const index = this.certidaoEmissores.findIndex((e) => e.codcertidaoemissor === codcertidaoemissor);
      if (index !== -1) {
        this.certidaoEmissores[index] = ret.data.data;
      }
      return ret;
    },

    async destroy(codcertidaoemissor) {
      const ret = await api.delete("v1/certidao-emissor/" + codcertidaoemissor);
      this.certidaoEmissores = this.certidaoEmissores.filter((e) => e.codcertidaoemissor !== codcertidaoemissor);
      return ret;
    },

    async inativar(codcertidaoemissor) {
      const ret = await api.post("v1/certidao-emissor/" + codcertidaoemissor + "/inativo");
      const index = this.certidaoEmissores.findIndex((e) => e.codcertidaoemissor === codcertidaoemissor);
      if (index !== -1) {
        this.certidaoEmissores[index] = ret.data.data;
      }
      return ret;
    },

    async ativar(codcertidaoemissor) {
      const ret = await api.delete("v1/certidao-emissor/" + codcertidaoemissor + "/inativo");
      const index = this.certidaoEmissores.findIndex((e) => e.codcertidaoemissor === codcertidaoemissor);
      if (index !== -1) {
        this.certidaoEmissores[index] = ret.data.data;
      }
      return ret;
    },
  },
});

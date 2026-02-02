import { defineStore } from "pinia";
import { api } from "boot/axios";

export const grauInstrucaoStore = defineStore("grauInstrucao", {
  state: () => ({
    grausInstrucao: [],
    filtro: {
      grauinstrucao: null,
      inativo: false,
    },
  }),

  actions: {
    async index() {
      const ret = await api.get("v1/grau-instrucao", { params: this.filtro });
      this.grausInstrucao = ret.data.data;
      return ret;
    },

    async get(codgrauinstrucao) {
      const ret = await api.get("v1/grau-instrucao/" + codgrauinstrucao);
      return ret;
    },

    async store(model) {
      const ret = await api.post("v1/grau-instrucao", model);
      this.grausInstrucao.push(ret.data.data);
      return ret;
    },

    async update(codgrauinstrucao, model) {
      const ret = await api.put("v1/grau-instrucao/" + codgrauinstrucao, model);
      const index = this.grausInstrucao.findIndex(
        (g) => g.codgrauinstrucao === codgrauinstrucao
      );
      if (index !== -1) {
        this.grausInstrucao[index] = ret.data.data;
      }
      return ret;
    },

    async destroy(codgrauinstrucao) {
      const ret = await api.delete("v1/grau-instrucao/" + codgrauinstrucao);
      this.grausInstrucao = this.grausInstrucao.filter(
        (g) => g.codgrauinstrucao !== codgrauinstrucao
      );
      return ret;
    },

    async inativar(codgrauinstrucao) {
      const ret = await api.post(
        "v1/grau-instrucao/" + codgrauinstrucao + "/inativo"
      );
      const index = this.grausInstrucao.findIndex(
        (g) => g.codgrauinstrucao === codgrauinstrucao
      );
      if (index !== -1) {
        this.grausInstrucao[index] = ret.data.data;
      }
      return ret;
    },

    async ativar(codgrauinstrucao) {
      const ret = await api.delete(
        "v1/grau-instrucao/" + codgrauinstrucao + "/inativo"
      );
      const index = this.grausInstrucao.findIndex(
        (g) => g.codgrauinstrucao === codgrauinstrucao
      );
      if (index !== -1) {
        this.grausInstrucao[index] = ret.data.data;
      }
      return ret;
    },
  },
});

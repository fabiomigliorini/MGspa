import { defineStore } from "pinia";
import { api } from "boot/axios";

export const feriadoStore = defineStore("feriado", {
  state: () => ({
    listagem: [],
  }),

  actions: {
    async getListagem() {
      const ret = await api.get("v1/feriado");
      this.listagem = ret.data.data;
      return ret;
    },

    async criar(model) {
      const ret = await api.post("v1/feriado", model);
      return ret;
    },

    async atualizar(codferiado, model) {
      const ret = await api.put("v1/feriado/" + codferiado, model);
      return ret;
    },

    async excluir(codferiado) {
      const ret = await api.delete("v1/feriado/" + codferiado);
      this.listagem = this.listagem.filter(
        (f) => f.codferiado !== codferiado
      );
      return ret;
    },

    async inativar(codferiado) {
      const ret = await api.post(
        "v1/feriado/" + codferiado + "/inativo"
      );
      const i = this.listagem.findIndex(
        (f) => f.codferiado === codferiado
      );
      if (i !== -1) this.listagem[i] = ret.data.data;
      return ret;
    },

    async ativar(codferiado) {
      const ret = await api.delete(
        "v1/feriado/" + codferiado + "/inativo"
      );
      const i = this.listagem.findIndex(
        (f) => f.codferiado === codferiado
      );
      if (i !== -1) this.listagem[i] = ret.data.data;
      return ret;
    },

    async gerarAno(ano) {
      const ret = await api.post("v1/feriado/gerar-ano", { ano });
      return ret;
    },
  },
});

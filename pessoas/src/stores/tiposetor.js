import { defineStore } from "pinia";
import { api } from "boot/axios";

export const tipoSetorStore = defineStore("tiposetor", {
  state: () => ({
    listagem: [],
  }),

  actions: {
    async getListagem() {
      const ret = await api.get("v1/tipo-setor");
      this.listagem = ret.data.data;
      return ret;
    },

    async criar(model) {
      const ret = await api.post("v1/tipo-setor", model);
      return ret;
    },

    async atualizar(codtiposetor, model) {
      const ret = await api.put("v1/tipo-setor/" + codtiposetor, model);
      return ret;
    },

    async excluir(codtiposetor) {
      const ret = await api.delete("v1/tipo-setor/" + codtiposetor);
      this.listagem = this.listagem.filter(
        (t) => t.codtiposetor !== codtiposetor
      );
      return ret;
    },

    async inativar(codtiposetor) {
      const ret = await api.post(
        "v1/tipo-setor/" + codtiposetor + "/inativo"
      );
      const i = this.listagem.findIndex(
        (t) => t.codtiposetor === codtiposetor
      );
      if (i !== -1) this.listagem[i] = ret.data.data;
      return ret;
    },

    async ativar(codtiposetor) {
      const ret = await api.delete(
        "v1/tipo-setor/" + codtiposetor + "/inativo"
      );
      const i = this.listagem.findIndex(
        (t) => t.codtiposetor === codtiposetor
      );
      if (i !== -1) this.listagem[i] = ret.data.data;
      return ret;
    },
  },
});

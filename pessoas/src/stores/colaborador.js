import { defineStore } from "pinia";
import { api } from "boot/axios";
import { ref } from "vue";

export const colaboradorStore = defineStore("colaborador", {
  state: () => ({
    colaboradores: [],
  }),

  getters: {},

  actions: {
    async getColaboradores(codpessoa) {
      const ret = await api.get("v1/pessoa/" + codpessoa + "/colaborador");
      this.colaboradores = ret.data.data;
      return ret;
    },

    async postFerias(model) {
      const ret = await api.post(
        "v1/colaborador/" + model.codcolaborador + "/ferias",
        model
      );
      const i = this.colaboradores.findIndex(
        (item) => item.codcolaborador === model.codcolaborador
      );
      this.colaboradores[i].Ferias.unshift(ret.data.data);
      return ret;
    },

    async deleteFerias(model) {
      const ret = await api.delete(
        "v1/colaborador/" + model.codcolaborador + "/ferias/" + model.codferias
      );
      const colaborador = this.findColaborador(model.codcolaborador);
      const i = colaborador.Ferias.findIndex((el) => {
        return el.codferias == model.codferias;
      });
      colaborador.Ferias.splice(i, 1);
      return true;
    },

    async putFerias(model) {
      const ret = await api.put(
        "v1/colaborador/" +
          model.codcolaborador +
          "/ferias/" +
          model.codferias,
        model
      );
      const colaborador = this.findColaborador(model.codcolaborador);
      const i = colaborador.Ferias.findIndex((el) => {
        return el.codferias == model.codferias;
      });
      colaborador.Ferias[i] = ret.data.data;
      return ret;
    },

    findColaborador(codcolaborador) {
      const ret = this.colaboradores.find((el) => {
        return el.codcolaborador == codcolaborador;
      });
      return ret;
    },
  },
});

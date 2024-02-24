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

    async salvarColaboradorCargo(model) {
      const ret = await api.put('v1/colaborador/' + model.codcolaborador +
        '/cargo/' + model.codcolaboradorcargo, model)

      const colaborador = this.findColaborador(model.codcolaborador);
      const i = colaborador.ColaboradorCargo.findIndex((el) => {
        return el.codcolaboradorcargo == model.codcolaboradorcargo;
      });
      colaborador.ColaboradorCargo[i] = ret.data.data;
      return ret;
    },

 
    async novoColaboradorCargo(model) {
      const ret = await api.post('v1/colaborador/cargo/', model)

      const i = this.colaboradores.findIndex(
        (item) => item.codcolaborador === model.codcolaborador
      );
      this.colaboradores[i] = ret.data.data;
      return ret;
    },


    async deleteColaboradorCargo(model) {

      const ret = await api.delete('v1/colaborador/cargo/' + model.codcolaboradorcargo)
      const colaborador = this.findColaborador(model.codcolaborador);
      const i = colaborador.ColaboradorCargo.findIndex((el) => {
        return el.codcolaboradorcargo == model.codcolaboradorcargo;
      });
      colaborador.ColaboradorCargo.splice(i, 1);
      return true;
    },

  
    async excluirColaborador(model) {
      const ret = await api.delete('v1/colaborador/' + model.codcolaborador)
      
      const colaborador = this.colaboradores.findIndex((el) => {
        return el.codcolaborador == model.codcolaborador;
      });

      this.colaboradores.splice(colaborador, 1);
      return true;
    },

    async salvarColaborador(modelEditColaborador) {
      const ret = await api.put('v1/colaborador/' + modelEditColaborador.codcolaborador, modelEditColaborador)
      return ret;
    },



    async novoColaborador(modelNovoColaborador) {
      const ret = await api.post('v1/colaborador/', modelNovoColaborador)
      return ret;
    },


  },
});

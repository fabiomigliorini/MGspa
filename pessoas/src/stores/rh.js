import { defineStore } from "pinia";
import { api } from "boot/axios";

export const rhStore = defineStore("rh", {
  state: () => ({
    periodos: [],
    dashboard: {},
    colaboradores: [],
  }),

  actions: {
    // --- PERÍODOS ---

    async getPeriodos() {
      const ret = await api.get("v1/rh/periodo");
      this.periodos = ret.data.data;
      return ret;
    },

    async criarPeriodo(model) {
      const ret = await api.post("v1/rh/periodo", model);
      return ret;
    },

    async duplicarPeriodo(codperiodo) {
      const ret = await api.post("v1/rh/periodo/" + codperiodo + "/duplicar");
      return ret;
    },

    async fecharPeriodo(codperiodo) {
      const ret = await api.post("v1/rh/periodo/" + codperiodo + "/fechar");
      return ret;
    },

    async reabrirPeriodo(codperiodo) {
      const ret = await api.post("v1/rh/periodo/" + codperiodo + "/reabrir");
      return ret;
    },

    async excluirPeriodo(codperiodo) {
      const ret = await api.delete("v1/rh/periodo/" + codperiodo);
      return ret;
    },

    async atualizarPeriodo(codperiodo, data) {
      const ret = await api.put("v1/rh/periodo/" + codperiodo, data);
      return ret;
    },

    // --- DASHBOARD ---

    async getDashboard(codperiodo) {
      const ret = await api.get("v1/rh/dashboard/" + codperiodo);
      this.dashboard = ret.data;
      return ret;
    },

    // --- COLABORADORES DO PERÍODO ---

    async getColaboradores(codperiodo) {
      const ret = await api.get("v1/rh/periodo/" + codperiodo + "/colaborador");
      this.colaboradores = ret.data.data;
      return ret;
    },

    async encerrar(codperiodo, codperiodocolaborador) {
      const ret = await api.post(
        "v1/rh/periodo/" + codperiodo + "/colaborador/" + codperiodocolaborador + "/encerrar"
      );
      return ret;
    },

    async estornar(codperiodo, codperiodocolaborador) {
      const ret = await api.post(
        "v1/rh/periodo/" + codperiodo + "/colaborador/" + codperiodocolaborador + "/estornar"
      );
      return ret;
    },

    async recalcular(codperiodo, codperiodocolaborador) {
      const ret = await api.post(
        "v1/rh/periodo/" + codperiodo + "/colaborador/" + codperiodocolaborador + "/recalcular"
      );
      return ret;
    },

    // --- VÍNCULOS COLABORADOR-SETOR ---

    async criarSetor(codperiodocolaborador, data) {
      const ret = await api.post(
        "v1/rh/periodo-colaborador/" + codperiodocolaborador + "/setor",
        data
      );
      return ret;
    },

    async atualizarSetor(codperiodocolaboradorsetor, data) {
      const ret = await api.put(
        "v1/rh/periodo-colaborador-setor/" + codperiodocolaboradorsetor,
        data
      );
      return ret;
    },

    async excluirSetor(codperiodocolaboradorsetor) {
      const ret = await api.delete(
        "v1/rh/periodo-colaborador-setor/" + codperiodocolaboradorsetor
      );
      return ret;
    },

    // --- RUBRICAS ---

    async criarRubrica(codperiodocolaborador, data) {
      const ret = await api.post(
        "v1/rh/periodo-colaborador/" + codperiodocolaborador + "/rubrica",
        data
      );
      return ret;
    },

    async atualizarRubrica(codcolaboradorrubrica, data) {
      const ret = await api.put("v1/rh/rubrica/" + codcolaboradorrubrica, data);
      return ret;
    },

    async excluirRubrica(codcolaboradorrubrica) {
      const ret = await api.delete("v1/rh/rubrica/" + codcolaboradorrubrica);
      return ret;
    },

    async toggleConcedido(codcolaboradorrubrica) {
      const ret = await api.patch(
        "v1/rh/rubrica/" + codcolaboradorrubrica + "/concedido"
      );
      return ret;
    },

    // --- INDICADORES ---

    async atualizarMeta(codindicador, data) {
      const ret = await api.put("v1/rh/indicador/" + codindicador + "/meta", data);
      return ret;
    },

    async lancamentoManual(codindicador, data) {
      const ret = await api.post(
        "v1/rh/indicador/" + codindicador + "/lancamento",
        data
      );
      return ret;
    },

    async getExtrato(codindicador) {
      const ret = await api.get(
        "v1/rh/indicador/" + codindicador + "/lancamento"
      );
      return ret.data.data;
    },
  },
});

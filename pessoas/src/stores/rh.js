import { defineStore } from "pinia";
import { api } from "boot/axios";

export const rhStore = defineStore("rh", {
  state: () => ({
    periodos: [],
    dashboard: {},
    colaboradores: [],
    indicadores: [],
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

    async getColaboradoresDisponiveis(codperiodo) {
      const ret = await api.get("v1/rh/periodo/" + codperiodo + "/colaborador/disponiveis");
      return ret.data.data;
    },

    async adicionarColaboradores(codperiodo, colaboradores) {
      const ret = await api.post("v1/rh/periodo/" + codperiodo + "/colaborador", { colaboradores });
      return ret;
    },

    async excluirColaborador(codperiodo, codperiodocolaborador) {
      const ret = await api.delete(
        "v1/rh/periodo/" + codperiodo + "/colaborador/" + codperiodocolaborador
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

    async getIndicadores(codperiodo) {
      const ret = await api.get("v1/rh/periodo/" + codperiodo + "/indicador");
      this.indicadores = ret.data.data;
      return ret;
    },

    async criarIndicador(codperiodo, data) {
      const ret = await api.post(
        "v1/rh/periodo/" + codperiodo + "/indicador",
        data
      );
      return ret;
    },

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

    async atualizarLancamento(codindicadorlancamento, data) {
      const ret = await api.put(
        "v1/rh/indicador-lancamento/" + codindicadorlancamento,
        data
      );
      return ret;
    },

    async excluirLancamento(codindicadorlancamento) {
      const ret = await api.delete(
        "v1/rh/indicador-lancamento/" + codindicadorlancamento
      );
      return ret;
    },

    async excluirIndicador(codindicador) {
      const ret = await api.delete("v1/rh/indicador/" + codindicador);
      return ret;
    },

    // --- REPROCESSAMENTO ---

    async reprocessarPeriodo(codperiodo, limpar = false) {
      const ret = await api.post("v1/rh/periodo/" + codperiodo + "/reprocessar", { limpar });
      return ret;
    },

    async progressoReprocessamento(codperiodo) {
      const ret = await api.get("v1/rh/periodo/" + codperiodo + "/reprocessar");
      return ret.data;
    },

    async cancelarReprocessamento(codperiodo) {
      const ret = await api.delete("v1/rh/periodo/" + codperiodo + "/reprocessar");
      return ret;
    },

    async getExtrato(codindicador, page = 1) {
      const ret = await api.get(
        "v1/rh/indicador/" + codindicador + "/lancamento",
        { params: { page } }
      );
      return ret.data;
    },
  },
});

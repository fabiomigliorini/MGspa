import { defineStore } from "pinia";
import { api } from "boot/axios";

export const empresaStore = defineStore("empresa", {
  persist: {
    paths: ["arrEmpresas", "filtroPesquisa"],
  },

  state: () => ({
    item: {},
    empresas: [],
    filtroPesquisa: {
      empresa: "",
      codempresa: "",
      page: 1,
      per_page: 20,
    },
    filiais: [],
    filial: {},
    filtroFilial: {
      filial: "",
    },
    loadingFiliais: false,
  }),

  actions: {
    async buscarFiliais(codempresa) {
      this.loadingFiliais = true;
      try {
        const ret = await api.get("v1/filial", {
          params: {
            codempresa,
            filial: this.filtroFilial.filial || undefined,
          },
        });
        this.filiais = ret.data.data;
        return ret;
      } finally {
        this.loadingFiliais = false;
      }
    },

    async getFilial(codfilial) {
      const ret = await api.get("v1/filial/" + codfilial);
      this.filial = ret.data.data;
      return ret;
    },

    async buscarEmpresas() {
      const ret = await api.get("v1/empresa", {
        params: this.filtroPesquisa,
      });
      if (this.filtroPesquisa.page === 1) {
        this.empresas = ret.data.data;
      } else {
        this.empresas = [...this.empresas, ...ret.data.data];
      }
      return ret;
    },

    async get(codempresa) {
      const ret = await api.get("v1/empresa/" + codempresa);
      this.item = ret.data.data;
      return ret;
    },

    async criarEmpresa(model) {
      const ret = await api.post("v1/empresa", model);
      return ret;
    },

    async atualizarEmpresa(codempresa, model) {
      const ret = await api.put("v1/empresa/" + codempresa, model);
      this.item = ret.data.data;
      return ret;
    },

    async removerFilial(codfilial) {
      const ret = await api.delete("v1/filial/" + codfilial);
      this.filiais = this.filiais.filter((f) => f.codfilial !== codfilial);
      return ret;
    },

    async removerEmpresa(codempresa) {
      const ret = await api.delete("v1/empresa/" + codempresa);
      this.empresas = this.empresas.filter((e) => e.codempresa !== codempresa);
      return ret;
    },

    limparFiltro() {
      this.filtroPesquisa = {
        empresa: "",
        codempresa: "",
        page: 1,
        per_page: 20,
      };
    },
  },
});

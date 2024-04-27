import { defineStore } from "pinia";
import { api } from "src/boot/axios";
import { pdvStore } from "./pdv";
import { sincronizacaoStore } from "./sincronizacao";
import moment from "moment";

const sPdv = pdvStore();
const sSinc = sincronizacaoStore();

export const liquidacaoStore = defineStore("liquidacao", {
  persist: {
    paths: ["filtroListagem", "listagem"],
  },
  state: () => ({
    opcoes: {
      integracao: ["Manual", "Integrado"],
    },
    counter: 0,
    listagem: [],
    filtro: {},
    paginacao: {
      current_page: 0,
      from: null,
      last_page: null,
      path: null,
      per_page: null,
      to: null,
      total: 0,
    },
  }),

  // getters: {
  //   doubleCount(state) {
  //     return state.counter * 2;
  //   },
  // },

  actions: {
    async inicializaFiltro() {
      if (Object.keys(this.filtro).length > 0) {
        return;
      }
      const filtro = {
        codpdv: null,
        codusuariocriacao: 1,
        codportador: null,
        codliquidacao: null,
        transacao_de: moment()
          .subtract(7, "d")
          .startOf("day")
          .format("DD/MM/YYYY HH:mm"),
        transacao_ate: moment().endOf("day").format("DD/MM/YYYY HH:mm"),
        pesquisar: "LIQ",
        codpessoa: null,
        tipo: null,
        valor_de: null,
        valor_ate: null,
        integracao: [],
      };
      const pdv = await sPdv.findByUuid(sSinc.pdv.uuid);
      if (pdv) {
        filtro.codpdv = pdv.codpdv;
      }
      this.filtro = filtro;
      await this.getLiquidacoes();
    },

    async getLiquidacoes() {
      this.paginacao.current_page = 0;
      this.paginacao.last_page = 99999;
      // this.negocios = [];
      await this.getLiquidacoesPaginacao();
    },

    async getLiquidacoesPaginacao() {
      if (this.paginacao.current_page >= this.paginacao.last_page) {
        return false;
      }
      try {
        const filtro = { ...this.filtro };
        filtro.pdv = sSinc.pdv.uuid;
        //converte data inicial
        var lanc = moment(filtro.transacao_de, "DD/MM/YYYY HH:mm", true);
        if (lanc.isValid()) {
          filtro.transacao_de = lanc.format("YYYY-MM-DD HH:mm:SS");
        } else {
          filtro.transacao_de = null;
        }
        //converte data final
        var lanc = moment(filtro.transacao_ate, "DD/MM/YYYY HH:mm", true);
        if (lanc.isValid()) {
          filtro.transacao_ate = lanc.format("YYYY-MM-DD HH:mm:SS");
        } else {
          filtro.transacao_ate = null;
        }
        filtro.page = this.paginacao.current_page + 1;
        const { data } = await api.get("/api/v1/pdv/liquidacao", {
          params: filtro,
        });
        if (filtro.page == 1) {
          this.listagem = data.data;
        } else {
          this.listagem = this.listagem.concat(data.data);
        }
        this.paginacao = data.meta;
      } catch (error) {
        var message = error?.response?.data?.message;
        if (!message) {
          message = error?.message;
        }
        Notify.create({
          type: "negative",
          message: message,
          timeout: 3000, // 3 segundos
          actions: [{ icon: "close", color: "white" }],
        });
        return false;
      }
    },
  },
});

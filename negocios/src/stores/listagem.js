import { defineStore } from "pinia";
import { api } from "src/boot/axios";
import { Notify } from "quasar";
import moment from "moment";
import { negocioStore } from "./negocio";
import { pdvStore } from "./pdv";
import { sincronizacaoStore } from "./sincronizacao";

const sNegocio = negocioStore();
const sPdv = pdvStore();
const sSinc = sincronizacaoStore();

export const listagemStore = defineStore("listagem", {
  persist: false,

  state: () => ({
    opcoes: {
      pagamento: ["PIX", "Cartão", "Dinheiro", "Prazo"],
      integracao: ["Manual", "Integrado"],
      codnegociostatus: [
        {
          label: "Aberto",
          value: 1,
        },
        {
          label: "Fechado",
          value: 2,
        },
        {
          label: "Cancelado",
          value: 3,
        },
      ],
    },
    filtro: {},
    negocios: [],
    carregando: true,
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

  actions: {
    async inicializaFiltro() {
      if (Object.keys(this.filtro).length > 0) {
        return;
      }
      const filtro = {
        codnegocio: null,
        lancamento_de: moment().startOf("day").format("DD/MM/YYYY HH:mm"),
        lancamento_ate: moment().endOf("day").format("DD/MM/YYYY HH:mm"),
        codestoquelocal: sNegocio.padrao.codestoquelocal,
        codnegociostatus: null,
        // codnaturezaoperacao: sNegocio.padrao.codnaturezaoperacao,
        codnaturezaoperacao: null,
        codpessoa: null,
        codpessoavendedor: null,
        codpessoatransportador: null,
        codpdv: null,
        codusuario: null,
        integracao: ["Manual", "Integrado"],
        pagamento: ["PIX", "Cartão", "Dinheiro", "Prazo"],
      };
      const pdv = await sPdv.findByUuid(sSinc.pdv.uuid);
      if (pdv) {
        filtro.codpdv = pdv.codpdv;
      }
      this.filtro = filtro;
      await this.getNegocios();
    },

    async getNegocios() {
      this.paginacao.current_page = 0;
      this.paginacao.last_page = 99999;
      this.negocios = [];
      this.carregando = false;
      await this.getNegociosPaginacao();
    },

    async getNegociosPaginacao() {
      if (this.carregando) {
        return;
      }
      if (this.paginacao.current_page >= this.paginacao.last_page) {
        return;
      }
      try {
        this.carregando = true;
        const filtro = { ...this.filtro };
        filtro.pdv = sSinc.pdv.uuid;
        //converte data inicial
        var lanc = moment(filtro.lancamento_de, "DD/MM/YYYY HH:mm", true);
        if (lanc.isValid()) {
          filtro.lancamento_de = lanc.format("YYYY-MM-DD HH:mm:SS");
        } else {
          filtro.lancamento_de = null;
        }
        //converte data final
        var lanc = moment(filtro.lancamento_ate, "DD/MM/YYYY HH:mm", true);
        if (lanc.isValid()) {
          filtro.lancamento_ate = lanc.format("YYYY-MM-DD HH:mm:SS");
        } else {
          filtro.lancamento_ate = null;
        }
        filtro.page = this.paginacao.current_page + 1;
        console.log(filtro);
        const { data } = await api.get("/api/v1/pdv/negocio", {
          params: filtro,
        });
        this.negocios = this.negocios.concat(data.data);
        this.paginacao = data.meta;
        this.carregando = false;
      } catch (error) {
        this.carregando = false;
        console.log(error);
        var message = error?.response?.data?.message;
        if (!message) {
          message = error?.message;
        }
        Notify.create({
          type: "negative",
          message: message,
          actions: [{ icon: "close", color: "white" }],
        });
        return false;
      }
    },
  },
});

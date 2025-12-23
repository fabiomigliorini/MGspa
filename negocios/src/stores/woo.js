import { defineStore } from "pinia";
import { api } from "src/boot/axios";
import { Notify } from "quasar";
import { unionBy, orderBy } from "lodash";
import moment from "moment";

export const wooStore = defineStore("woo", {
  persist: false,

  state: () => ({
    opcoes: {
      status: [
        { label: "Pagamento Pendente", value: "pending" },
        { label: "Processando", value: "processing" },
        { label: "Aguardando", value: "on-hold" },
        { label: "Concluído", value: "completed" },
        { label: "Cancelado", value: "cancelled" },
        { label: "Reembolsado", value: "refunded" },
        { label: "Malsucedido", value: "failed" },
        { label: "Rascunho", value: "checkout-draft" },
      ],
    },
    filtro: {},
    pedidos: [],
    orcamentos: [],
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

  getters: {
    pedidosPorIdDesc() {
      return orderBy(this.pedidos, ["id"], ["desc"]);
    },
  },

  actions: {
    statusLabel(value) {
      const item = this.opcoes.status.find((i) => i.value == value);
      return item.label;
    },

    async inicializaFiltro() {
      if (Object.keys(this.filtro).length > 0) {
        return;
      }
      const filtro = {
        id: null,
        nome: null,
        status: [],
        criacaowoo_de: moment()
          .subtract(7, "d")
          .startOf("day")
          .format("DD/MM/YYYY"),
        criacaowoo_ate: moment().endOf("day").format("DD/MM/YYYY"),
        valortotal_de: null,
        valortotal_ate: null,
      };
      const pdv = await sPdv.findByUuid(sSinc.pdv.uuid);
      if (pdv) {
        filtro.codpdv = pdv.codpdv;
      }
      this.filtro = filtro;
      await this.getPedidos();
    },

    async getPedidos() {
      this.paginacao.current_page = 0;
      this.paginacao.last_page = 99999;
      this.pedidos = [];
      await this.getPedidosPaginacao();
    },

    async getPedidosPaginacao() {
      if (this.paginacao.current_page >= this.paginacao.last_page) {
        return false;
      }
      try {
        const filtro = { ...this.filtro };
        const { data } = await api.get("/api/v1/woo/pedido", {
          params: filtro,
        });
        this.pedidos = this.pedidos.concat(data.data);
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

    async reprocessarPedido(id) {
      try {
        const { data } = await api.post(
          "/api/v1/woo/pedido/" + id + "/reprocessar"
        );
        const ped = data.data;
        const i = this.pedidos.findIndex((item) => item.id == id);
        this.pedidos[i] = ped;
        return true;
      } catch (error) {
        console.log(error);
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

    async buscarNovos() {
      try {
        const { data } = await api.post("/api/v1/woo/pedido/buscar-novos");
        this.pedidos = unionBy(data.data, this.pedidos, "codwoopedido");
        return data.data.length;
      } catch (error) {
        console.log(error);
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

    async buscarPorAlteracao() {
      try {
        const { data } = await api.post(
          "/api/v1/woo/pedido/buscar-por-alteracao"
        );
        this.pedidos = unionBy(data.data, this.pedidos, "codwoopedido");
        return data.data.length;
      } catch (error) {
        console.log(error);
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

    async alterarStatus(id, status) {
      try {
        const { data } = await api.put("/api/v1/woo/pedido/" + id + "/status", {
          status,
        });
        const ped = data.data;
        const i = this.pedidos.findIndex((item) => item.id == id);
        if (i !== -1) {
          this.pedidos[i] = ped;
        }
        return true;
      } catch (error) {
        console.log(error);
        var message = error?.response?.data?.message;
        if (!message) {
          message = error?.message;
        }
        Notify.create({
          type: "negative",
          message: message,
          timeout: 3000,
          actions: [{ icon: "close", color: "white" }],
        });
        return false;
      }
    },
  },
});

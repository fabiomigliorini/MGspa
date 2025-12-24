import { defineStore } from "pinia";
import { api } from "src/boot/axios";
import { Notify } from "quasar";
import { unionBy, orderBy } from "lodash";
import { negocioStore } from "./negocio";
import moment from "moment";
const sNegocio = negocioStore();

export const wooStore = defineStore("woo", {
  persist: false,

  state: () => ({
    opcoes: {
      status: [
        // {
        //   ordem: 1,
        //   label: "Cliente Ainda Comprando",
        //   value: "checkout-draft",
        //   cor: "bg-grey",
        // },
        {
          ordem: 2,
          label: "Aguardando Cliente Pagar",
          value: "pending",
          cor: "bg-purple",
        },
        {
          ordem: 3,
          label: "Aguardando Confirmação Pagamento",
          value: "on-hold",
          cor: "bg-orange",
        },
        {
          ordem: 4,
          label: "Em Separação",
          value: "processing",
          cor: "bg-orange",
        },
        {
          ordem: 5,
          label: "Concluído",
          value: "completed",
          cor: "bg-secondary",
        },
        {
          ordem: 6,
          label: "Cancelado",
          value: "cancelled",
          cor: "bg-negative",
        },
        {
          ordem: 7,
          label: "Reembolsado",
          value: "refunded",
          cor: "bg-negative",
        },
        { ordem: 8, label: "Falha", value: "failed", cor: "bg-negative" },
      ],
    },
    filtro: {},
    pedidos: [],
    pedido: {},
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

  getters: {},

  actions: {
    pedidosPorIdDesc() {
      return orderBy(this.pedidos, ["id"], ["desc"]);
    },

    colunasKanban() {
      const ret = this.opcoes.status;
      return orderBy(ret, ["ordem"], ["asc"]);
    },

    async refreshPedido() {
      if (!this.pedido) {
        return true;
      }

      // atualiza pedido aberto no modal
      this.pedido = this.pedidos.find(
        (p) => p.codwoopedido == this.pedido.codwoopedido
      );

      // atualiza dados do pedido se estiver
      // vinculado com o negocio aberto
      if (sNegocio.negocio) {
        if (sNegocio.negocio.WooPedidoS) {
          const i = sNegocio.negocio.WooPedidoS.findIndex(
            (p) => (p.codwoopedido = this.pedido.codwoopedido)
          );
          if (i > -1) {
            await sNegocio.recarregarDaApi(sNegocio.negocio.codnegocio);
          }
        }
      }
    },

    statusLabel(value) {
      const item = this.opcoes.status.find((i) => i.value == value);
      return item.label;
    },

    statusColor(value) {
      const item = this.opcoes.status.find((i) => i.value == value);
      return item.cor;
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
      return await this.getPedidosPaginacao();
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
        if (this.paginacao.current_page == 0) {
          this.pedidos = data.data;
        } else {
          this.pedidos = unionBy(data.data, this.pedidos, "codwoopedido");
        }
        this.paginacao = data.meta;
        this.refreshPedido();
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
        this.paginacao.current_page = 9999;
        this.paginacao.last_page = 0;
        return false;
      }
    },

    async getPedidosPainel() {
      try {
        const { data } = await api.get("/api/v1/woo/pedido/painel");
        this.pedidos = unionBy(data.data, this.pedidos, "codwoopedido");
        this.refreshPedido();
        return data.data.length;
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
        this.pedidos = unionBy([data.data], this.pedidos, "codwoopedido");
        this.refreshPedido();
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
        this.refreshPedido();
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
        this.refreshPedido();
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
        this.pedidos = unionBy([data.data], this.pedidos, "codwoopedido");
        this.refreshPedido();
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

import { defineStore } from "pinia";
import { api } from "src/boot/axios";
import { negocioStore } from "./negocio";
import { Notify } from "quasar";

const sNegocio = negocioStore();

export const pagarMeStore = defineStore("pagarMe", {
  state: () => ({
    pedido: {},
    pedidosPendentes: {},
    dialog: {
      detalhesPedido: false,
    },
  }),

  actions: {
    async consultarPedidosPendentes() {
      try {
        const { data } = await api.get("/api/v1/pdv/pagar-me/pedido/pendentes");
        this.pedidosPendentes = data.data;
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

    async importarPedidosPendentes() {
      try {
        const { data } = await api.patch(
          "/api/v1/pdv/pagar-me/pedido/pendentes"
        );
        this.pedidosPendentes = data.data;
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

    async consultarPedido() {
      try {
        const { data } = await api.post(
          "/api/v1/pdv/pagar-me/pedido/" +
            this.pedido.codpagarmepedido +
            "/consultar"
        );
        this.pedido = data.data;
        await this.atualizarPagarMePedido();
        Notify.create({
          type: "positive",
          message: "Consulta Efetuada!",
          timeout: 1000, // 1 segundo
          actions: [{ icon: "close", color: "white" }],
        });
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

    async cancelarPedido() {
      try {
        const { data } = await api.delete(
          "/api/v1/pdv/pagar-me/pedido/" + this.pedido.codpagarmepedido
        );
        this.pedido = data.data;
        await this.atualizarPagarMePedido();
        Notify.create({
          type: "positive",
          message: "Cancelamento Efetuado!",
          timeout: 1000, // 1 segundo
          actions: [{ icon: "close", color: "white" }],
        });
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

    async atualizarPagarMePedido() {
      // se nao estiver vinculado com negocio desiste
      if (!this.pedido.codnegocio) {
        return;
      }

      // se nao estiver com negocio aberto desiste
      if (!sNegocio.negocio) {
        return;
      }

      // se nao estiver com o mesmo negocio desiste
      if (this.pedido.codnegocio != sNegocio.negocio.codnegocio) {
        return;
      }

      // se o negocio nao estiver sincronizado desiste
      if (!sNegocio.negocio.sincronizado) {
        return;
      }

      // recarrega negocio da api
      await sNegocio.recarregarDaApi(sNegocio.negocio.codnegocio);
    },
  },
});

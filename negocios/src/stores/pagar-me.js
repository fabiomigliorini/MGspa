import { defineStore } from "pinia";
import { api } from "src/boot/axios";
import { negocioStore } from "./negocio";
import { Notify } from "quasar";

const sNegocio = negocioStore();

export const pagarMeStore = defineStore("pagarMe", {
  state: () => ({
    pedido: {},
    dialog: {
      detalhesPedido: false,
    },
  }),

  actions: {
    async consultarPedido() {
      try {
        const { data } = await api.post(
          "/api/v1/pdv/pagar-me/pedido/" +
            this.pedido.codpagarmepedido +
            "/consultar"
        );
        this.pedido = data.data;
        this.atualizarPagarMePedido();
        Notify.create({
          type: "positive",
          message: "Consulta Efetuada!",
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
          actions: [{ icon: "close", color: "white" }],
        });
        return false;
      }
    },

    atualizarPagarMePedido() {
      // se nao estiver com o mesmo negocio desiste
      if (this.pedido.codnegocio != sNegocio.negocio.codnegocio) {
        return;
      }

      // se o negocio nao estiver sincronizado desiste
      if (!sNegocio.negocio.sincronizado) {
        return;
      }

      // recarrega negocio da api
      sNegocio.recarregarDaApi(sNegocio.negocio.codnegocio);
    },
  },
});

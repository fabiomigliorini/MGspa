import { defineStore } from "pinia";
import { sincronizacaoStore } from "./sincronizacao";
import { api } from "src/boot/axios";
import { Notify } from "quasar";

const sSinc = sincronizacaoStore();

export const mercosStore = defineStore("mercos", {
  // persist: {
  //   // paths: ["padrao", "paginaAtual", "ultimos"],
  // },

  state: () => ({
    pedidos: [],
  }),

  getters: {
    quantidade() {
      return this.negocios.length;
    },
  },

  actions: {
    async importarPedido() {
      try {
        const { data } = await api.post("/api/v1/pdv/mercos/pedido/importar", {
          pdv: sSinc.pdv.uuid,
        });
        let message = "Importadas " + data.importados + " vendas!";
        Notify.create({
          type: "positive",
          message: message,
          timeout: 3000, // 3 segundos
          actions: [{ icon: "close", color: "white" }],
        });
        if (data.erros > 0) {
          let message = "Erro ao importar " + data.erros + " vendas!";
          Notify.create({
            type: "negative",
            message: message,
            timeout: 0,
            actions: [{ icon: "close", color: "white" }],
          });
        }
        this.pedidos = data.listagem;
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
          timeout: 0,
          actions: [{ icon: "close", color: "white" }],
        });
        return false;
      }
    },

    async atualizarListagem() {
      try {
        const { data } = await api.get("/api/v1/pdv/mercos/pedido", {
          params: { pdv: sSinc.pdv.uuid },
        });
        this.pedidos = data;
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
          timeout: 0,
          actions: [{ icon: "close", color: "white" }],
        });
        return false;
      }
    },
  },
});
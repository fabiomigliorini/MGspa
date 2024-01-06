import { defineStore } from "pinia";
import { api } from "src/boot/axios";
import { negocioStore } from "./negocio";
import { Notify } from "quasar";

const sNegocio = negocioStore();

export const pixStore = defineStore("pix", {
  // persist: {
  //   paths: ["padrao", "paginaAtual"],
  // },

  state: () => ({
    pixCob: {},
    dialog: {
      detalhesPixCob: false,
    },
  }),

  getters: {
    quantidadeProdutosAtivos() {
      // return this.itensAtivos.length;
    },
  },

  actions: {
    async transmitirPixCob() {
      try {
        const { data } = await api.post(
          "/api/v1/pix/cob/" + this.pixCob.codpixcob + "/transmitir"
        );
        this.pixCob = data.data;
        this.atualizarPixCobNegocio();
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

    async consultarPixCob() {
      try {
        const { data } = await api.post(
          "/api/v1/pix/cob/" + this.pixCob.codpixcob + "/consultar"
        );
        this.pixCob = data.data;
        this.atualizarPixCobNegocio();
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

    atualizarPixCobNegocio() {
      // se nao estiver com o mesmo negocio desiste
      if (this.pixCob.codnegocio != sNegocio.negocio.codnegocio) {
        return;
      }

      // se o negocio nao estiver sincronizado desiste
      if (!sNegocio.negocio.sincronizado) {
        return;
      }

      // procura pix cob no negcoio
      const index = sNegocio.negocio.pixCob.findIndex(
        (pixCob) => (pixCob.codpixcob = this.pixCob.codpixcob)
      );

      // se nao existir recarrega da api
      if (index == -1) {
        sNegocio.recarregarDaApi(sNegocio.negocio.codnegocio);
        return;
      }

      // se for o mesmo  status só substitui, nem recarrega
      if (
        sNegocio.negocio.pixCob[index].codpixcobstatus ==
        this.pixCob.codpixcobstatus
      ) {
        sNegocio.negocio.pixCob[index] = this.pixCob;
        sNegocio.salvar(false);
        return;
      }

      // recarrega negocio da api
      sNegocio.recarregarDaApi(sNegocio.negocio.codnegocio);
    },
  },
});

/*
curl 'https://api-mgspa.mgpapelaria.com.br/api/v1/pix/cob/72504/imprimir-qr-code'
*/

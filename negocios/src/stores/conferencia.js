import { defineStore } from "pinia";
import { api } from "src/boot/axios";
import { Notify } from "quasar";
import moment from "moment";
import { pdvStore } from "./pdv";
import { sincronizacaoStore } from "./sincronizacao";

const sPdv = pdvStore();
const sSinc = sincronizacaoStore();

export const conferenciaStore = defineStore("conferencia", {
  persist: false,

  state: () => ({
    filtro: {},
    conferencias: [],
  }),

  actions: {
    async inicializaFiltro() {
      if (Object.keys(this.filtro).length > 0) {
        return;
      }
      const filtro = {
        dia: moment().startOf("day").format("DD/MM/YYYY"),
        codpdv: null,
      };
      const pdv = await sPdv.findByUuid(sSinc.pdv.uuid);
      if (pdv) {
        filtro.codpdv = pdv.codpdv;
      }
      this.filtro = filtro;
      await this.getConferencia();
    },

    async getConferencia() {
   
      try {
        const filtro = { ...this.filtro };
        filtro.pdv = sSinc.pdv.uuid;
        //converte data inicial
        var dia = moment(filtro.dia, "DD/MM/YYYY");
        if (dia.isValid()) {
          filtro.dia = dia.format("YYYY-MM-DD");
        } else {
          filtro.dia = null;
        }
        //converte data final
       
        const ret = await api.get("/api/v1/pdv/negocio/conferencia", {
          params: filtro,
        });
          this.conferencias = ret.data;
      } catch (error) {
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

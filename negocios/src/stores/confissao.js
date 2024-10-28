import { defineStore } from "pinia";
import { api } from "src/boot/axios";
import { sincronizacaoStore } from "./sincronizacao";
import { Notify } from "quasar";

const sSinc = sincronizacaoStore();

export const confissaoStore = defineStore("confissao", {
  persist: false,

  state: () => ({
    imagem: null,
    ratio: null,
    codnegocio: null,
    valor: null,
    encontrados: 0,
  }),

  actions: {
    async novaImagem(imagem, ratio) {
      this.imagem = imagem;
      this.ratio = ratio;
      this.codnegocio = null;
      this.valor = null;
      this.encontrados = null;
      this.sugerirCodnegocio();
    },

    async sugerirCodnegocio() {
      try {
        this.inicializarSugestao();
        const ret = await api.post("/api/v1/pdv/negocio/anexo/sugerir", {
          pdv: sSinc.pdv.uuid,
          anexoBase64: this.imagem,
        });
        this.codnegocio = ret.data.codnegocio;
        this.valor = ret.data.valor;
        this.encontrados = ret.data.encontrados;
        this.notificacaoEncontrado();
        return true;
      } catch (error) {
        console.log(error);
        return false;
      }
    },

    async inicializarSugestao() {
      this.codnegocio = null;
      this.valor = null;
      this.encontrados = null;
    },

    async notificacaoEncontrado() {
      if (this.encontrados) {
        Notify.create({
          type: "positive",
          message: "Negocio localizado!",
          timeout: 3000, // 3 segundos
          actions: [{ icon: "close", color: "white" }],
        });
        return;
      }
      Notify.create({
        type: "negative",
        message: "Negocio não localizado!",
        timeout: 3000, // 3 segundos
        actions: [{ icon: "close", color: "white" }],
      });
    },

    async upload() {
      try {
        const ret = await sSinc.uploadAnexo(
          this.codnegocio,
          "confissao",
          this.ratio,
          this.imagem
        );
        if (!ret) {
          return false;
        }
        Notify.create({
          type: "positive",
          message: "Anexo Adicionado!",
          timeout: 1000, // 1 segundo
          actions: [{ icon: "close", color: "white" }],
        });
        this.inicializarSugestao();
        this.imagem = null;
      } catch (error) {
        console.log(error);
        return false;
      }
    },

    async procurar() {
      try {
        const ret = await api.post("/api/v1/pdv/negocio/anexo/procurar", {
          pdv: sSinc.pdv.uuid,
          codnegocio: this.codnegocio,
          valor: this.valor,
        });
        this.codnegocio = ret.data.codnegocio;
        this.valor = ret.data.valor;
        this.encontrados = ret.data.encontrados;
        this.notificacaoEncontrado();
        return true;
      } catch (error) {
        console.log(error);
        return false;
      }
    },
  },
});

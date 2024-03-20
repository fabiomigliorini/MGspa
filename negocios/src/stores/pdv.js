import { defineStore } from "pinia";
import { api } from "src/boot/axios";
import { Notify } from "quasar";

export const pdvStore = defineStore("pdv", {
  state: () => ({
    dispositivos: [],
  }),

  actions: {
    async getDispositivos() {
      try {
        const { data } = await api.get("/api/v1/pdv/dispositivo");
        this.dispositivos = data.data;
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

    async autorizar(pdv) {
      try {
        const { data } = await api.post(
          `/api/v1/pdv/dispositivo/${pdv.codpdv}/autorizado`
        );
        pdv = data.data;
        const i = this.dispositivos.findIndex((el) => {
          return el.codpdv == pdv.codpdv;
        });
        this.dispositivos[i] = pdv;
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

    async desautorizar(pdv) {
      try {
        const { data } = await api.delete(
          `/api/v1/pdv/dispositivo/${pdv.codpdv}/autorizado`
        );
        pdv = data.data;
        const i = this.dispositivos.findIndex((el) => {
          return el.codpdv == pdv.codpdv;
        });
        this.dispositivos[i] = pdv;
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

    async inativar(pdv) {
      try {
        const { data } = await api.post(
          `/api/v1/pdv/dispositivo/${pdv.codpdv}/inativo`
        );
        pdv = data.data;
        const i = this.dispositivos.findIndex((el) => {
          return el.codpdv == pdv.codpdv;
        });
        this.dispositivos[i] = pdv;
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

    async reativar(pdv) {
      try {
        const { data } = await api.delete(
          `/api/v1/pdv/dispositivo/${pdv.codpdv}/inativo`
        );
        pdv = data.data;
        const i = this.dispositivos.findIndex((el) => {
          return el.codpdv == pdv.codpdv;
        });
        this.dispositivos[i] = pdv;
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

    async findByUuid(uuid) {
      if (this.dispositivos.length == 0) {
        await this.getDispositivos();
      }
      return this.dispositivos.find((el) => {
        return el.uuid == uuid;
      });
    },
  },
});

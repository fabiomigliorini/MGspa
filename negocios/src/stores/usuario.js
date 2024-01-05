import { defineStore } from "pinia";
import { api } from "boot/axios";
import { Notify } from "quasar";

export const usuarioStore = defineStore("usuario", {
  persist: {
    paths: ["usuario", "token"],
  },

  state: () => ({
    usuario: {},
    token: {},
  }),

  getters: {},

  actions: {
    async login(usuario, senha) {
      const params = {
        username: usuario,
        password: senha,
        grant_type: "password",
        client_id: process.env.CLIENT_ID,
        client_secret: process.env.CLIENT_SECRET,
      };
      try {
        let { data } = await api.post("/oauth/token", params);
        if (data.access_token) {
          this.token = data;
          const miliseconds = data.expires_in * 1000;
          this.token.expires_at = new Date(Date.now() + miliseconds);
          this.getUsuario();
        }
        return true;
      } catch (error) {
        Notify.create({
          type: "negative",
          message: "Usuário ou Senha inválidos!",
        });
        console.log(error);
        return false;
      }
    },

    async refreshToken() {
      const params = {
        refresh_token: this.token.refresh_token,
        grant_type: "refresh_token",
        client_id: process.env.CLIENT_ID,
        client_secret: process.env.CLIENT_SECRET,
      };
      try {
        let { data } = await api.post("/oauth/token", params);
        if (data.access_token) {
          this.token = data;
          this.getUsuario();
        }
      } catch (error) {
        Notify.create({
          type: "negative",
          message: "Falha ao renovar token de autenticação!",
        });
        console.log(error);
      }
    },

    async logout() {
      try {
        let { data } = await api.get("/api/v1/auth/logout");
        this.usuario = {};
        this.token = {};
      } catch (error) {
        console.log(error);
      }
    },

    async getUsuario() {
      if (this.token.access_token) {
        try {
          let { data } = await api.get("/api/v1/auth/user");
          this.usuario = data.data;
        } catch (error) {
          console.log(error);
          this.usuario = {};
        }
      }
      return this.usuario;
    },
  },
});

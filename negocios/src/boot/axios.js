import { boot } from "quasar/wrappers";
import axios from "axios";
import { usuarioStore } from "stores/usuario";
const sUsuario = usuarioStore();

const api = axios.create({ baseURL: process.env.API_BASE_URL });

api.interceptors.request.use(
  (config) => {
    // Autorizacao
    let tokenCookie = document.cookie
      .split(";")
      .find((c) => c.trim().startsWith("access_token="));
    if (tokenCookie) {
      sUsuario.token.access_token = tokenCookie.split("=")[1];
    }

    if (sUsuario.token.access_token) {
      config.headers["Authorization"] = "Bearer " + sUsuario.token.access_token;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

api.interceptors.response.use(
  (response) => {
    // Retorna a resposta sem alteração se tudo estiver OK
    return response;
  },
  (error) => {
    // Verifica se a resposta do erro existe e se o status é 401 (nao autenticado)
    if (error.response && error.response.status === 401) {
      // Limpa o cookie de autenticação
      document.cookie =
        "access_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";

      // Limpa o token e o usuario no store
      sUsuario.usuario = {};
      sUsuario.token = {};

      // abre o dialog de login
      sUsuario.dialog.login = true;

      return Promise.reject(error);
    }
    // Para qualquer outro erro, o erro é propagado
    return Promise.reject(error);
  }
);

export default boot(({ app }) => {
  app.config.globalProperties.$axios = axios;
  app.config.globalProperties.$api = api;
});

export { api };

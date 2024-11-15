import { boot } from "quasar/wrappers";
import axios from "axios";
import { usuarioStore } from "stores/usuario";
const sUsuario = usuarioStore();

// Be careful when using SSR for cross-request state pollution
// due to creating a Singleton instance here;
// If any client changes this (global) instance, it might be a
// good idea to move this instance creation inside of the
// "export default () => {}" function below (which runs individually
// for each client)
const api = axios.create({ baseURL: process.env.API_BASE_URL });

api.interceptors.request.use(
  (config) => {
    // Autorizacao
    //get access_token from cookie

    let tokenCookie = document.cookie.split(";").find((c) => c.trim().startsWith("access_token="));
    if (tokenCookie) {
      sUsuario.token.access_token = tokenCookie.split("=")[1];
    }

    if (sUsuario.token.access_token) {
      config.headers["Authorization"] = "Bearer " + sUsuario.token.access_token;
    }
    return config;
  },
  (error) => {
    Promise.reject(error);
  }
);

export default boot(({ app }) => {
  // for use inside Vue files (Options API) through this.$axios and this.$api

  app.config.globalProperties.$axios = axios;
  // ^ ^ ^ this will allow you to use this.$axios (for Vue Options API form)
  //       so you won't necessarily have to import axios in each vue file

  app.config.globalProperties.$api = api;
  // ^ ^ ^ this will allow you to use this.$api (for Vue Options API form)
  //       so you can easily perform requests against your app's API
});

export { api };

import { route } from 'quasar/wrappers'
import {
  createRouter,
  createMemoryHistory,
  createWebHistory,
  createWebHashHistory,
} from 'vue-router'
import routes from './routes'

/*
 * If not building with SSR mode, you can
 * directly export the Router instantiation;
 *
 * The function below can be async too; either use
 * async/await or return a Promise which resolves
 * with the Router instance.
 */

export default route(function ({ store }) {
  const createHistory = process.env.SERVER
    ? createMemoryHistory
    : process.env.VUE_ROUTER_MODE === 'history'
      ? createWebHistory
      : createWebHashHistory

  const Router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes,

    // Leave this as is and make changes in quasar.conf.js instead!
    // quasar.conf.js -> build -> vueRouterMode
    // quasar.conf.js -> build -> publicPath
    history: createHistory(process.env.VUE_ROUTER_BASE),
  })

  // Navegador sem cadastro ou com o dispositivo inativo (PDV e quiosque) so' usa a area de
  // dispositivos: o Meu Dispositivo mostra o status, tem o Cadastrar e e' onde o Administrador
  // ativa
  // (TASK-46). A checagem e' local, vale offline; a pagina do proprio dispositivo atualiza.
  // Import dinamico: o store puxa o boot/axios, que usa o Pinia ainda inexistente quando o
  // router e' criado.
  Router.beforeEach(async (to) => {
    const { sincronizacaoStore } = await import('stores/sincronizacao')
    const { pdv } = sincronizacaoStore(store)
    if ((pdv.codpdv && !pdv.inativo) || to.path.startsWith('/dispositivo')) {
      return true
    }
    return '/dispositivo/meu'
  })

  return Router
})

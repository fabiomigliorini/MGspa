import { route } from 'quasar/wrappers'
import { createRouter, createMemoryHistory, createWebHistory, createWebHashHistory } from 'vue-router'
import routes from './routes'
import { guardaToken } from 'stores/index'
import { api } from 'src/boot/axios'


/*
 * If not building with SSR mode, you can
 * directly export the Router instantiation;
 *
 * The function below can be async too; either use
 * async/await or return a Promise which resolves
 * with the Router instance.
 */

export default route(function (/* { store, ssrContext } */) {
  const createHistory = process.env.SERVER
    ? createMemoryHistory
    : (process.env.VUE_ROUTER_MODE === 'history' ? createWebHistory : createWebHashHistory)

  const Router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes,

    // Leave this as is and make changes in quasar.conf.js instead!
    // quasar.conf.js -> build -> vueRouterMode
    // quasar.conf.js -> build -> publicPath
    history: createHistory(process.env.MODE === 'ssr' ? void 0 : process.env.VUE_ROUTER_BASE)
  })

  Router.beforeEach(async (to, from, next) => {
  
    if (to.meta?.auth) {
      const auth = guardaToken()
  
      if (auth.token){
       const EstaAutenticado = await auth.verificaToken()
        
        if(EstaAutenticado) {
    
          auth.username(EstaAutenticado.usuario)
          // Envia sempre o Token em todas as requisições
          api.defaults.headers.common['Authorization'] = 'Bearer ' + auth.token
          next()
        }else {
          api.defaults.headers.common['Authorization'] = ''
          next({name: 'login'})
        } 
      }else {
        api.defaults.headers.common['Authorization'] = ''
        next({name: 'login'})
      }   
    }else {
      next()
    }
    
  })
  return Router
})

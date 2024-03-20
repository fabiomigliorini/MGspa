import { store } from 'quasar/wrappers'
import { createPinia } from 'pinia'
import { ref } from 'vue'
import { defineStore } from 'pinia'
import { api } from 'src/boot/axios'
import { createRouter, createWebHistory } from 'vue-router'
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate'
/*
 * If not building with SSR mode, you can
 * directly export the Store instantiation;
 *
 * The function below can be async too; either use
 * async/await or return a Promise which resolves
 * with the Store instance.
 */

export default store(() => {

  const pinia = createPinia()
  // You can add Pinia plugins here
  // pinia.use(SomePiniaPlugin)
  pinia.use(piniaPluginPersistedstate)

  return pinia;
})

export const router = createRouter({
  history: createWebHistory('/'),
  linkActiveClass: 'active',
  routes: [
    { path: '/' },
    { path: '/login' }
  ]
})

// router.beforeEach(async (to) => {
//   // redirect to login page if not logged in and trying to access a restricted page
//   const publicPages = ['/login'];
//   const authRequired = !publicPages.includes(to.path);
//   const auth = guardaToken();

//   if (authRequired && !auth.user) {
//       auth.returnUrl = to.fullPath;
//       return '/login'
//   }
// })



export const guardaToken = defineStore('auth', () => {

  state: () => ({
    // initialize state from local storage to enable user to stay logged in
    user: JSON.parse(localStorage.getItem('usuario')),
    returnUrl: null,
    usuarioLogado: {},
    urlRetorno: {}
  })

  const token = ref(localStorage.getItem('access_token'))
  const user = ref(localStorage.getItem('usuario'))

  function accessToken(tokenValue) {

    localStorage.setItem('access_token', tokenValue)
    token.value = tokenValue
  }
  

  function username(userValue) {

    localStorage.setItem('usuario', userValue)
    user.value = userValue

  }

  function verificaPermissaoUsuario(permissao) {
   const verificaPermissao = this.usuarioLogado.permissoes.find(grupo => grupo.grupousuario === permissao)

   const admin = this.usuarioLogado.permissoes.find(grupo => grupo.grupousuario === 'Administrador')
   if(admin){
    return admin;
   }
   return verificaPermissao;
  }

  // Acessa os dados do usuario como verificação se o token esta valido na API
  async function verificaToken() {
    try {
      const tokenverificacao = 'Bearer ' + token.value
      const { data } = await api.get('v1/auth/user', {
        headers: {
          Authorization: tokenverificacao
        }
      })

      if (data.data.usuario) {
        this.usuarioLogado = data.data
        return data.data;
      } else {
        localStorage.removeItem('access_token')
        localStorage.removeItem('usuario')
        return false
      }
    } catch (error) {
      localStorage.removeItem('access_token')
      localStorage.removeItem('usuario')
    }
  }
  return {
    token,
    guardaToken,
    accessToken,
    verificaPermissaoUsuario,
    username,
    verificaToken
  };
})



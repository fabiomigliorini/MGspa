import { store } from 'quasar/wrappers'
import { createPinia, defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from 'src/boot/axios'
import { createRouter, createWebHistory } from 'vue-router'


/*
 * If not building with SSR mode, you can
 * directly export the Store instantiation;
 *
 * The function below can be async too; either use
 * async/await or return a Promise which resolves
 * with the Store instance.
 */

export default store((/* { ssrContext } */) => {
  const pinia = createPinia()

  // You can add Pinia plugins here
  // pinia.use(SomePiniaPlugin)

  return pinia
})

export const router = createRouter({
  history: createWebHistory('/'),
  linkActiveClass: 'active',
  routes: [
    { path: '/' },
    { path: '/login' }
  ]
})

export const guardaToken = defineStore('auth', () => {

  state: () => ({
    // initialize state from local storage to enable user to stay logged in
    user: JSON.parse(localStorage.getItem('usuario')),
    returnUrl: null,
    usuarioLogado: {}
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




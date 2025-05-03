import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from 'src/boot/axios'

export const authStore = defineStore('auth', ()=>{
  /*state: () => ({
    // initialize state from local storage to enable user to stay logged in
    user: JSON.parse(localStorage.getItem('usuario')),
    returnUrl: null,
    usuarioLogado: {},
    urlRetorno: {}
  })*/

  const token = ref(localStorage.getItem('access_token'))
  const user = ref(localStorage.getItem('usuario'))
  //const returnUrl = null
  //const usuarioLogado = {}
  //const urlRetorno = {}

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
      let tokenCookie = document.cookie.split(";").find((c) => c.trim().startsWith("access_token="));
      if (tokenCookie) {
        token.value = tokenCookie.split("=")[1];
      }
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
    } catch  {
      localStorage.removeItem('access_token')
      localStorage.removeItem('usuario')
      let url = new URL(window.location.href)
      url = encodeURI(url.origin)
      window.location.href = process.env.API_AUTH_URL + '/login?redirect_uri=' + url
    }
  }

  return {
    token,
    authStore,
    accessToken,
    verificaPermissaoUsuario,
    username,
    verificaToken
  };
})

/*if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useMyStore, import.meta.hot))
}*/

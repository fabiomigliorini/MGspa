import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

// Instância axios dedicada (sem interceptors de src/boot/axios.js).
// Evita loop no interceptor 401.
const api = axios.create({
  baseURL: process.env.API_URL,
})

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('access_token'))
  const usuario = ref(null)
  const carregando = ref(false)

  function gravarToken(novoToken) {
    token.value = novoToken
    if (novoToken) {
      localStorage.setItem('access_token', novoToken)
    } else {
      localStorage.removeItem('access_token')
    }
  }

  function tokenDoCookie() {
    const cookie = document.cookie.split(';').find((c) => c.trim().startsWith('access_token='))
    return cookie ? cookie.split('=')[1] : null
  }

  async function validarToken() {
    const tokenAtivo = tokenDoCookie() || token.value
    if (!tokenAtivo) return false

    carregando.value = true
    try {
      const response = await api.get('v1/auth/user', {
        headers: { Authorization: `Bearer ${tokenAtivo}` },
      })

      if (response.data?.data?.usuario) {
        usuario.value = response.data.data
        if (tokenAtivo !== token.value) gravarToken(tokenAtivo)
        return true
      }
      return false
    } catch (error) {
      console.error('Erro ao validar token:', error)
      gravarToken(null)
      usuario.value = null
      return false
    } finally {
      carregando.value = false
    }
  }

  function temPermissao(nome) {
    if (!usuario.value?.permissoes) return false
    const grupos = usuario.value.permissoes.map((p) => p.grupousuario)
    if (grupos.includes('Administrador')) return true
    return grupos.includes(nome)
  }

  function temAlgumaPermissao(lista) {
    if (!usuario.value?.permissoes) return false
    const grupos = usuario.value.permissoes.map((p) => p.grupousuario)
    if (grupos.includes('Administrador')) return true
    return lista.some((p) => grupos.includes(p))
  }

  function limparSessao() {
    gravarToken(null)
    usuario.value = null
    localStorage.removeItem('usuario')
    document.cookie =
      'access_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=.mgpapelaria.com.br;'
    document.cookie =
      'user_id=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=.mgpapelaria.com.br;'
  }

  async function logout() {
    const tokenAtivo = tokenDoCookie() || token.value
    try {
      if (tokenAtivo) {
        await axios.post(
          process.env.API_AUTH_URL + '/api/logout',
          {},
          { headers: { Authorization: 'Bearer ' + tokenAtivo } },
        )
      }
    } catch (error) {
      console.warn('Erro ao fazer logout no backend:', error)
    } finally {
      limparSessao()
      const url = encodeURIComponent(window.location.href)
      window.location.href = process.env.API_AUTH_URL + '/login?redirect_uri=' + url
    }
  }

  return {
    token,
    usuario,
    carregando,
    gravarToken,
    validarToken,
    logout,
    temPermissao,
    temAlgumaPermissao,
  }
})

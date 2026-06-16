import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

// /userinfo e /oauth/revoke vivem em path raiz do API_AUTH_URL —
// usamos axios direto (sem instância com baseURL=API_URL).

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('access_token'))
  const usuario = ref(null)
  const expiresAt = ref(null)
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
      // OIDC Core 1.0 §5.3 — /userinfo (raiz) retorna claims OIDC + custom MGspa
      // num único objeto plano (sem wrapper `data`).
      // Timeout curto: sem ele, um socket morto reaproveitado trava o guard do
      // router (e a tela) por minutos esperando o /userinfo responder.
      const response = await axios.get(`${process.env.API_AUTH_URL}/userinfo`, {
        headers: { Authorization: `Bearer ${tokenAtivo}` },
        timeout: 2000,
      })

      if (response.data?.usuario) {
        usuario.value = response.data
        const expAt = response.data?.meta?.expires_at
        expiresAt.value = expAt ? new Date(expAt) : null
        if (tokenAtivo !== token.value) gravarToken(tokenAtivo)
        return true
      }
      return false
    } catch (error) {
      // Falha de rede / timeout (sem response): otimista — não desloga, deixa
      // navegar. Se o token estiver de fato expirado, o interceptor 401 desloga
      // na primeira chamada real. Só limpa em erro com resposta do servidor.
      if (!error.response) {
        console.warn('Validação de token sem resposta (rede/timeout), seguindo otimista:', error)
        return true
      }
      console.error('Erro ao validar token:', error)
      gravarToken(null)
      usuario.value = null
      expiresAt.value = null
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
    expiresAt.value = null
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
        // RFC 7009 — revoga o token passando-o no body + client_credentials
        const params = new URLSearchParams({
          token: tokenAtivo,
          token_type_hint: 'access_token',
          client_id: process.env.CLIENT_ID,
          client_secret: process.env.CLIENT_SECRET,
        })
        await axios.post(`${process.env.API_AUTH_URL}/oauth/revoke`, params)
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
    expiresAt,
    carregando,
    gravarToken,
    validarToken,
    logout,
    temPermissao,
    temAlgumaPermissao,
  }
})

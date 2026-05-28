import Vue from 'vue'
import axios from 'axios'

// Auth service plain JS (singleton reativo via Vue.observable),
// equivalente ao Pinia store useAuthStore() dos apps Vue 3 (contas/notas/pessoas).
//
// Padrão OAuth/OIDC:
//   - token persistido em localStorage('access_token')
//   - validarToken() bate em ${API_AUTH_URL}/userinfo (Bearer)
//   - logout() revoga via ${API_AUTH_URL}/oauth/revoke (RFC 7009)
//
// Usamos axios "cru" (sem interceptors) porque /userinfo e /oauth/revoke
// vivem no path raiz do API_AUTH_URL — separado do baseURL do app.

const REDIRECT_KEY = 'redirect_after_login'

const state = Vue.observable({
  token: localStorage.getItem('access_token') || null,
  usuario: null,
  expiresAt: null,
  carregando: false
})

function gravarToken (novoToken) {
  state.token = novoToken
  if (novoToken) {
    localStorage.setItem('access_token', novoToken)
  } else {
    localStorage.removeItem('access_token')
  }
}

function gravarUrlRetorno (url) {
  if (url) {
    localStorage.setItem(REDIRECT_KEY, url)
  } else {
    localStorage.removeItem(REDIRECT_KEY)
  }
}

function consumirUrlRetorno () {
  const url = localStorage.getItem(REDIRECT_KEY)
  gravarUrlRetorno(null)
  return url
}

async function validarToken () {
  if (!state.token) return false
  state.carregando = true
  try {
    const response = await axios.get(`${process.env.API_AUTH_URL}/userinfo`, {
      headers: { Authorization: `Bearer ${state.token}` }
    })
    if (response.data && response.data.usuario) {
      state.usuario = response.data
      const expAt = response.data.meta && response.data.meta.expires_at
      state.expiresAt = expAt ? new Date(expAt) : null
      return true
    }
    return false
  } catch (error) {
    console.error('Erro ao validar token:', error)
    gravarToken(null)
    state.usuario = null
    state.expiresAt = null
    return false
  } finally {
    state.carregando = false
  }
}

function temGrupo (grupoNome) {
  if (!state.usuario || !state.usuario.permissoes) return false
  return state.usuario.permissoes.some(p => p.grupousuario === grupoNome)
}

function temAlgumaPermissao (lista) {
  if (!state.usuario || !state.usuario.permissoes) return false
  const grupos = state.usuario.permissoes.map(p => p.grupousuario)
  if (grupos.includes('Administrador')) return true
  return lista.some(p => grupos.includes(p))
}

function redirecionarParaLogin (urlRetornoFullPath) {
  if (urlRetornoFullPath) {
    gravarUrlRetorno(urlRetornoFullPath)
  }
  const currentUrl = encodeURIComponent(window.location.origin + '/#/login')
  window.location.href = `${process.env.API_AUTH_URL}/login?redirect_uri=${currentUrl}`
}

async function logout () {
  try {
    let tokenUsado = state.token
    const cookieToken = document.cookie
      .split(';')
      .find(item => item.trim().startsWith('access_token='))
    if (cookieToken) {
      tokenUsado = cookieToken.split('=')[1]
    }
    if (tokenUsado) {
      const params = new URLSearchParams({
        token: tokenUsado,
        token_type_hint: 'access_token',
        client_id: process.env.CLIENT_ID,
        client_secret: process.env.CLIENT_SECRET
      })
      await axios.post(`${process.env.API_AUTH_URL}/oauth/revoke`, params)
    }
  } catch (error) {
    console.warn('Erro ao revogar token no backend:', error)
  } finally {
    gravarToken(null)
    state.usuario = null
    state.expiresAt = null
    window.location.href = '/'
  }
}

export default {
  state,
  gravarToken,
  gravarUrlRetorno,
  consumirUrlRetorno,
  validarToken,
  logout,
  temGrupo,
  temAlgumaPermissao,
  redirecionarParaLogin
}

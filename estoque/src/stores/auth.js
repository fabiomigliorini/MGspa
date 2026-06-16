import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

// Usamos axios direto (sem instância dedicada) porque /userinfo e /oauth/revoke
// vivem em path raiz do API_AUTH_URL — separado do baseURL do app (API_URL).
// Sem interceptors também evita loop no 401 (validarToken falha → interceptor
// chamaria gravarToken(null) → redirect → valida → 401 → loop).

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('access_token'))
  const usuario = ref(null)
  const expiresAt = ref(null)
  const carregando = ref(false)
  const urlRetorno = ref(localStorage.getItem('redirect_after_login'))

  function gravarToken(novoToken) {
    token.value = novoToken
    if (novoToken) {
      localStorage.setItem('access_token', novoToken)
    } else {
      localStorage.removeItem('access_token')
    }
  }

  function gravarUrlRetorno(url) {
    urlRetorno.value = url
    if (url) {
      localStorage.setItem('redirect_after_login', url)
    } else {
      localStorage.removeItem('redirect_after_login')
    }
  }

  function consumirUrlRetorno() {
    const url = urlRetorno.value
    gravarUrlRetorno(null)
    return url
  }

  async function validarToken() {
    if (!token.value) return false

    carregando.value = true
    try {
      // OIDC Core 1.0 §5.3 — /userinfo (raiz, sem prefixo /api) retorna claims
      // OIDC + custom MGspa num único objeto plano (sem wrapper `data`).
      // Timeout curto: sem ele, um socket morto reaproveitado trava o guard do
      // router (e a tela) por minutos esperando o /userinfo responder.
      const response = await axios.get(`${process.env.API_AUTH_URL}/userinfo`, {
        headers: { Authorization: `Bearer ${token.value}` },
        skipLoading: true,
        timeout: 2000,
      })

      if (response.data?.usuario) {
        usuario.value = response.data
        const expAt = response.data?.meta?.expires_at
        expiresAt.value = expAt ? new Date(expAt) : null
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

  function temAlgumaPermissao(lista) {
    if (!usuario.value?.permissoes) return false

    const grupos = usuario.value.permissoes.map((p) => p.grupousuario)

    if (grupos.includes('Administrador')) return true

    return lista.some((p) => grupos.includes(p))
  }

  function temGrupo(grupoNome) {
    if (!usuario.value?.permissoes) return false
    return usuario.value.permissoes.some((p) => p.grupousuario === grupoNome)
  }

  function filiaisDoGrupo(grupoNome) {
    if (!usuario.value?.permissoes) return []
    const filiais = []
    for (const p of usuario.value.permissoes) {
      if (p.grupousuario === grupoNome) {
        for (const f of p.filiais || []) {
          if (f.codfilial != null) filiais.push(f.codfilial)
        }
      }
    }
    return [...new Set(filiais)]
  }

  // null = sem restrição (Administrador/Financeiro/Cobranca);
  // array = filiais permitidas para Caixa+Gerente.
  function filiaisRestritas() {
    if (!usuario.value?.permissoes) return []
    if (temGrupo('Administrador') || temGrupo('Financeiro') || temGrupo('Cobranca')) {
      return null
    }
    return [...new Set([...filiaisDoGrupo('Caixa'), ...filiaisDoGrupo('Gerente')])]
  }

  async function logout() {
    try {
      let tokenUsado = token.value

      const cookieToken = document.cookie
        .split(';')
        .find((item) => item.trim().startsWith('access_token='))

      if (cookieToken) {
        tokenUsado = cookieToken.split('=')[1]
      }

      if (tokenUsado) {
        // RFC 7009 — revoga o token passando-o no body + client_credentials
        const params = new URLSearchParams({
          token: tokenUsado,
          token_type_hint: 'access_token',
          client_id: process.env.CLIENT_ID,
          client_secret: process.env.CLIENT_SECRET,
        })
        await axios.post(`${process.env.API_AUTH_URL}/oauth/revoke`, params)
      }
    } catch (error) {
      console.warn('Erro ao fazer logout no backend:', error)
    } finally {
      gravarToken(null)
      usuario.value = null
      expiresAt.value = null
      localStorage.removeItem('access_token')
      localStorage.removeItem('usuario')
      window.location.href = '/'
    }
  }

  return {
    token,
    usuario,
    expiresAt,
    carregando,
    urlRetorno,
    gravarToken,
    gravarUrlRetorno,
    consumirUrlRetorno,
    validarToken,
    logout,
    temAlgumaPermissao,
    temGrupo,
    filiaisDoGrupo,
    filiaisRestritas,
  }
})

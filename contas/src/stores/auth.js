import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

// Instância axios dedicada (sem interceptors de src/boot/axios.js).
// Evita loop no interceptor 401: se o validarToken falhasse com 401, o
// interceptor chamaria gravarToken(null) → redireciona → valida de novo → 401 → loop.
const api = axios.create({
  baseURL: process.env.API_URL,
})

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
      const response = await api.get('v1/auth/user', {
        headers: { Authorization: `Bearer ${token.value}` },
        skipLoading: true,
      })

      if (response.data?.data?.usuario) {
        usuario.value = response.data.data
        const expAt = response.data?.meta?.expires_at
        expiresAt.value = expAt ? new Date(expAt) : null
        return true
      }
      return false
    } catch (error) {
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
        await api.post(
          `${process.env.API_AUTH_URL}/api/logout`,
          {},
          {
            headers: {
              Authorization: `Bearer ${tokenUsado}`,
            },
          },
        )
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

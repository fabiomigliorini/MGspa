import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

const api = axios.create({
  baseURL: process.env.API_URL,
})

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('access_token'))
  const user = ref(null)
  const loading = ref(false)

  function setToken(newToken) {
    token.value = newToken
    if (newToken) {
      localStorage.setItem('access_token', newToken)
    } else {
      localStorage.removeItem('access_token')
    }
  }

  async function validateToken() {
    if (!token.value) return false

    loading.value = true
    try {
      const response = await api.get('v1/auth/user', {
        headers: { Authorization: `Bearer ${token.value}` },
      })

      if (response.data?.data?.usuario) {
        user.value = response.data.data
        return true
      }
      return false
    } catch (error) {
      console.error('Erro ao validar token:', error)
      setToken(null)
      user.value = null
      return false
    } finally {
      loading.value = false
    }
  }

  function hasAnyPermission(permissionsList) {
    if (!user.value?.permissoes) return false

    const userPermissions = user.value.permissoes.map((p) => p.grupousuario)

    // Administrador tem acesso a tudo
    if (userPermissions.includes('Administrador')) return true

    // Verifica se tem pelo menos uma das permissões
    return permissionsList.some((p) => userPermissions.includes(p))
  }

  async function logout() {
    try {
      // Pega o token do cookie (prioriza) ou do localStorage
      let tokenToUse = token.value

      const cookieToken = document.cookie
        .split(';')
        .find((item) => item.trim().startsWith('access_token='))

      if (cookieToken) {
        tokenToUse = cookieToken.split('=')[1]
      }

      // Faz logout no backend (SSO)
      if (tokenToUse) {
        console.log('post')
        console.log(`${process.env.API_AUTH_URL}/api/logout`)
        await api.post(
          `${process.env.API_AUTH_URL}/api/logout`, // <-- USA A VARIÁVEL
          {},
          {
            headers: {
              Authorization: `Bearer ${tokenToUse}`,
            },
          },
        )
      }
    } catch (error) {
      console.warn('Erro ao fazer logout no backend:', error)
      // Continua mesmo com erro
    } finally {
      // Limpa dados locais
      setToken(null)
      user.value = null
      localStorage.removeItem('access_token')
      localStorage.removeItem('usuario')
      // Redireciona para home (que vai detectar falta de token e redirecionar pro SSO)
      window.location.href = '/#/'
    }
  }

  return {
    token,
    user,
    loading,
    setToken,
    validateToken,
    logout,
    hasAnyPermission,
  }
})

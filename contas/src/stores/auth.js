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
  const redirectUrl = ref(localStorage.getItem('redirect_after_login'))

  function setToken(newToken) {
    token.value = newToken
    if (newToken) {
      localStorage.setItem('access_token', newToken)
    } else {
      localStorage.removeItem('access_token')
    }
  }

  function setRedirectUrl(url) {
    redirectUrl.value = url
    if (url) {
      localStorage.setItem('redirect_after_login', url)
    } else {
      localStorage.removeItem('redirect_after_login')
    }
  }

  function getAndClearRedirectUrl() {
    const url = redirectUrl.value
    setRedirectUrl(null)
    return url
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

    if (userPermissions.includes('Administrador')) return true

    return permissionsList.some((p) => userPermissions.includes(p))
  }

  async function logout() {
    try {
      let tokenToUse = token.value

      const cookieToken = document.cookie
        .split(';')
        .find((item) => item.trim().startsWith('access_token='))

      if (cookieToken) {
        tokenToUse = cookieToken.split('=')[1]
      }

      if (tokenToUse) {
        await api.post(
          `${process.env.API_AUTH_URL}/api/logout`,
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
    } finally {
      setToken(null)
      user.value = null
      localStorage.removeItem('access_token')
      localStorage.removeItem('usuario')
      window.location.href = '/'
    }
  }

  return {
    token,
    user,
    loading,
    redirectUrl,
    setToken,
    setRedirectUrl,
    getAndClearRedirectUrl,
    validateToken,
    logout,
    hasAnyPermission,
  }
})

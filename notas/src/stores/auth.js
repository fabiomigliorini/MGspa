import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

const api = axios.create({
  baseURL: process.env.API_URL
})

// const api = axios.create({
//   baseURL: '/api'  // <-- Mude para isso (vai usar o proxy)
// })

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
        headers: { Authorization: `Bearer ${token.value}` }
      })

      if (response.data?.data?.usuario) {
        user.value = response.data.data
        return true
      }
      return false
    } catch (error) {
      console.error('Erro ao validar token:', error)
      setToken(null)
      return false
    } finally {
      loading.value = false
    }
  }

  function logout() {
    setToken(null)
    user.value = null
    window.location.href = process.env.API_AUTH_URL + '/login'
  }

  function hasAnyPermission(permissionsList) {
    if (!user.value?.permissoes) return false

    const userPermissions = user.value.permissoes.map(p => p.grupousuario)

    // Administrador tem acesso a tudo
    if (userPermissions.includes('Administrador')) return true

    // Verifica se tem pelo menos uma das permissões
    return permissionsList.some(p => userPermissions.includes(p))
  }

  return { token, user, loading, setToken, validateToken, logout, hasAnyPermission }
})
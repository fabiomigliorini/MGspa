import { boot } from 'quasar/wrappers'
import axios from 'axios'
import { useAuthStore } from 'src/stores/auth'
import { Notify } from 'quasar'

// Cria instância da API
const api = axios.create({
  baseURL: process.env.API_URL,
})

export default boot(({ app }) => {
  // <-- REMOVE 'router' aqui

  // ===== REQUEST INTERCEPTOR =====
  api.interceptors.request.use(
    (config) => {
      const authStore = useAuthStore()

      if (authStore.token) {
        config.headers.Authorization = `Bearer ${authStore.token}`
      }

      return config
    },
    (error) => {
      return Promise.reject(error)
    },
  )

  // ===== RESPONSE INTERCEPTOR =====
  api.interceptors.response.use(
    (response) => {
      return response
    },
    (error) => {
      const authStore = useAuthStore()

      if (error.response) {
        const status = error.response.status

        switch (status) {
          case 401: {
            // <-- ADICIONE { } para criar bloco
            // Token inválido ou expirado
            console.warn('Token expirado ou inválido (401)')
            authStore.setToken(null)
            authStore.user = null

            Notify.create({
              type: 'negative',
              message: 'Sessão expirada. Faça login novamente.',
              position: 'top',
            })

            // Redireciona para login
            const currentUrl = encodeURIComponent(window.location.origin + '/#/login')
            setTimeout(() => {
              window.location.href = `${process.env.API_AUTH_URL}/login?redirect_uri=${currentUrl}`
            }, 1500)
            break
          } // <-- FECHE o bloco

          case 403:
            Notify.create({
              type: 'negative',
              message: 'Você não tem permissão para esta ação',
              position: 'top',
            })
            break

          case 404:
            console.warn('Recurso não encontrado (404):', error.config.url)
            break

          case 422:
            // Erros de validação - será tratado no componente
            break

          case 500:
            Notify.create({
              type: 'negative',
              message: 'Erro no servidor. Tente novamente.',
              position: 'top',
            })
            break
        }
      } else if (error.request) {
        Notify.create({
          type: 'negative',
          message: 'Erro de conexão. Verifique sua internet.',
          position: 'top',
        })
      }

      return Promise.reject(error)
    },
  )

  // Disponibiliza globalmente
  app.config.globalProperties.$axios = axios
  app.config.globalProperties.$api = api
})

export { api }

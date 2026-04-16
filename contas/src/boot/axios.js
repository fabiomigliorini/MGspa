import { boot } from 'quasar/wrappers'
import axios from 'axios'
import { useAuthStore } from 'src/stores/auth'
import { Notify, LoadingBar } from 'quasar'
import { api } from 'src/services/api'

export default boot(({ app }) => {
  // ===== REQUEST INTERCEPTOR =====
  api.interceptors.request.use(
    (config) => {
      const authStore = useAuthStore()

      if (authStore.token) {
        config.headers.Authorization = `Bearer ${authStore.token}`
      }

      if (!config.skipLoading) {
        LoadingBar.start()
      }

      return config
    },
    (error) => {
      LoadingBar.stop()
      return Promise.reject(error)
    },
  )

  // ===== RESPONSE INTERCEPTOR =====
  api.interceptors.response.use(
    (response) => {
      if (!response.config?.skipLoading) {
        LoadingBar.stop()
      }
      return response
    },
    (error) => {
      if (!error.config?.skipLoading) {
        LoadingBar.stop()
      }

      const authStore = useAuthStore()

      if (error.response) {
        const status = error.response.status
        console.error('Erro na requisição', error)

        switch (status) {
          case 401: {
            console.warn('Token expirado ou inválido (401)')
            authStore.setToken(null)
            authStore.user = null

            Notify.create({
              type: 'negative',
              message: 'Sessão expirada. Faça login novamente.',
            })

            const currentUrl = encodeURIComponent(window.location.origin + '/login')
            setTimeout(() => {
              window.location.href = `${process.env.API_AUTH_URL}/login?redirect_uri=${currentUrl}`
            }, 1500)
            break
          }

          case 403:
            Notify.create({
              type: 'negative',
              message: 'Você não tem permissão para esta ação',
            })
            break

          case 404:
            console.warn('Recurso não encontrado (404):', error.config.url)
            break

          case 422:
            // Validação: tratar no componente via extrairErro(e)
            break

          case 500:
            Notify.create({
              type: 'negative',
              message: 'Erro no servidor. Tente novamente em instantes.',
            })
            break
        }
      } else if (error.code === 'ECONNABORTED') {
        Notify.create({
          type: 'negative',
          message: 'Tempo esgotado. Tente novamente.',
          position: 'top',
        })
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

  app.config.globalProperties.$axios = axios
  app.config.globalProperties.$api = api
})

export { axios, api }

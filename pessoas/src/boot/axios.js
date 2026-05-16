import { boot } from 'quasar/wrappers'
import axios from 'axios'
import { Notify } from 'quasar'
import { useAuthStore } from 'src/stores/auth'

const api = axios.create({
  baseURL: process.env.API_URL,
  headers: { 'Content-type': 'application/json' },
})

export default boot(({ app }) => {
  api.interceptors.request.use(
    (config) => {
      const authStore = useAuthStore()
      const cookie = document.cookie.split(';').find((c) => c.trim().startsWith('access_token='))
      const tokenAtivo = (cookie ? cookie.split('=')[1] : null) || authStore.token

      if (tokenAtivo) {
        config.headers.Authorization = `Bearer ${tokenAtivo}`
      }
      return config
    },
    (error) => Promise.reject(error),
  )

  api.interceptors.response.use(
    (response) => response,
    (error) => {
      if (error.response?.status === 401) {
        const authStore = useAuthStore()
        Notify.create({
          type: 'negative',
          message: 'Sessão expirada. Faça login novamente.',
        })
        setTimeout(() => authStore.logout(), 1500)
      }
      return Promise.reject(error)
    },
  )

  app.config.globalProperties.$axios = axios
  app.config.globalProperties.$api = api
})

export { api }

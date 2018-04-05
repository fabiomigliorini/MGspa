import axios from 'axios'
import { Notify, Loading } from 'quasar'
import refresh from '../jwt/Refresh'

export default ({ Vue }) => {
  Vue.prototype.$axios = axios.create({
    baseURL: process.env.API_URL,
    'X-Requested-With': 'XMLHttpRequest'
  })

  Vue.prototype.$axios.interceptors.request.use(function (config) {
    const AUTH_TOKEN = localStorage.getItem('auth.token')
    if (AUTH_TOKEN) {
      config.headers.common['Authorization'] = `Bearer ${AUTH_TOKEN}`
    }
    Loading.show()
    return config
  }, function (error) {
    return Promise.reject(error)
  })

  Vue.prototype.$axios.interceptors.response.use((response) => {
    Loading.hide()
    return response
  }, function (error) {
    Loading.hide()
    console.log('erro: ' + error)
    let mensagem = 'Erro ao acessar API'
    if (error.response) {
      if (error.response.status) {
        const originalRequest = error.config
        if (error.response.status === 401 && !originalRequest._retry) {
          refresh.handle(error.response)
        }
        mensagem += ' - ' + error.response.status
        if (error.response.data.mensagem) {
          mensagem += ' - ' + error.response.data.mensagem
        }
      }
    }
    Notify.create(mensagem)
    return Promise.reject(error)
  })
}

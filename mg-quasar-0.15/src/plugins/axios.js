import axios from 'axios'
// import { Notify, Loading } from 'quasar'
// import refresh from '../jwt/Refresh'

export default ({ Vue }) => {
  Vue.prototype.$axios = axios.create({
    baseURL: process.env.API_BASE_URL,
    'X-Requested-With': 'XMLHttpRequest'
  })
}

// axios.interceptors.response.use(function (response) {
//   console.log('Interceptado: ' + response)
//   return response
// }, function (error) {
//   // Do something with response error
//   return Promise.reject(error)
// })

// axios.interceptors.response.use((response) => {
//   console.log('Interceptado')
//   Loading.hide()
//   return response
// }, function (error) {
//   Loading.hide()
//   let mensagem = 'Erro ao acessar API'
//   if (error.response) {
//     if (error.response.status) {
//       const originalRequest = error.config
//       if (error.response.status === 401 && !originalRequest._retry) {
//         refresh.handle(error.response)
//       }
//       mensagem += ' - ' + error.response.status
//       if (error.response.data.mensagem) {
//         mensagem += ' - ' + error.response.data.mensagem
//       }
//     }
//   }
//   Notify.create(mensagem)
//   return Promise.reject(error)
// })

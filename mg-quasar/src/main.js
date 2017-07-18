// === DEFAULT / CUSTOM STYLE ===
// WARNING! always comment out ONE of the two require() calls below.
// 1. use next line to activate CUSTOM STYLE (./src/themes)
// require(`./themes/app.${__THEME}.styl`)
// 2. or, use next line to activate DEFAULT QUASAR STYLE
require(`quasar/dist/quasar.${__THEME}.css`)
// ==============================

import Vue from 'vue'
import Quasar from 'quasar'
import router from './router'
import store from './store'

Vue.use(Quasar) // Install Quasar Framework

// Axios
import Axios from 'axios'
window.axios = Axios.create({
  baseURL: process.env.API_BASE_URL,
  'X-Requested-With': 'XMLHttpRequest'
})
window.axios.interceptors.request.use(function (config) {
  const AUTH_TOKEN = localStorage.getItem('auth.token')
  if (AUTH_TOKEN) {
    config.headers.common['Authorization'] = `Bearer ${AUTH_TOKEN}`
  }

  return config
}, function (error) {
  return Promise.reject(error)
})

window.axios.interceptors.response.use((response) => {
  return response
}, function (error) {
  const originalRequest = error.config
  if (error.response.status === 401 && !originalRequest._retry) {
    return router.push('/login/')
  }
  /*
  let mensagem = error.response.status
  if (error.response.data.mensagem) {
    mensagem += ' - ' + error.response.data.mensagem
  }
  else {
    mensagem += ' - Erro ao acessar API'
  }
  store.commit('snackbar/error', mensagem)
  */
  return Promise.reject(error)
})

Quasar.start(() => {
  /* eslint-disable no-new */
  new Vue({
    el: '#q-app',
    router,
    store,
    render: h => h(require('./App'))
  })
})

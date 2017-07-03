// Padrão Vue
import Vue from 'vue'
import App from './App'
import router from './router'

Vue.config.productionTip = false

// Vuetify
import Vuetify from 'vuetify'
import 'vuetify/dist/vuetify.min.css'
import 'material-design-icons/iconfont/material-icons.css'
Vue.use(Vuetify)

// Precisamos descobrir o que é
window._ = require('lodash')

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
  let originalRequest = error.config

  if (error.response.status === 401 && !originalRequest._retry) {
    console.log('originalRequest: ' + JSON.stringify(originalRequest.url))
    return router.push('/login/')
  }

  return Promise.reject(error)
})

// Inciializa Vue
/* eslint-disable no-new */
new Vue({
  el: '#app',
  router,
  template: '<App/>',
  components: {
    App
  }
})
